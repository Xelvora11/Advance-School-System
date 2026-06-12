<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\Guardian;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentDiscount;
use App\Models\StudentDocument;
use App\Models\StudentFeeLedgerEntry;
use App\Models\StudentFine;
use App\Models\User;
use App\Support\Activity;
use App\Support\SchoolContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $schoolId = SchoolContext::id();
        $query = Student::with(['schoolClass', 'section'])->forSchool($schoolId)->latest();

        if ($request->filled('search')) {
            $query->where(function ($inner) use ($request) {
                $inner->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('registration_number', 'like', '%'.$request->search.'%')
                    ->orWhere('guardian_phone', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('class_id')) {
            $query->where('school_class_id', $request->integer('class_id'));
        }

        if ($request->filled('section_id')) {
            $query->where('section_id', $request->integer('section_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return view('school.students.index', [
            'students' => $query->paginate(15)->withQueryString(),
            'classes' => SchoolClass::forSchool($schoolId)->orderBy('sort_order')->get(),
            'sections' => Section::forSchool($schoolId)->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('school.students.form', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        $this->validateParentLogin($request);

        $student = DB::transaction(function () use ($request, $validated) {
            $validated['school_id'] = SchoolContext::id();
            $validated['registration_number'] = blank($validated['registration_number'] ?? null)
                ? $this->nextRegistrationNumber()
                : $validated['registration_number'];

            if ($request->hasFile('photo')) {
                $validated['photo_path'] = $request->file('photo')->store('students/'.SchoolContext::id(), 'public');
            }

            $student = Student::create($validated);
            $this->syncGuardian($student, $request);

            return $student;
        });

        Activity::log('student_created', 'Student created: '.$student->name, ['student_id' => $student->id]);

        return redirect()->route('students.show', $student)->with('status', 'Student added.');
    }

    public function show(Student $student): View
    {
        $this->authorizeStudent($student);

        $student->load([
            'user',
            'schoolClass',
            'section',
            'guardians.user',
            'attendance.schoolClass',
            'attendance.section',
            'fees' => fn ($query) => $query->with(['feeHead', 'payments.receiver'])->latest(),
            'discounts' => fn ($query) => $query->with(['studentFee.feeHead', 'creator'])->latest('discount_date'),
            'fines' => fn ($query) => $query->with(['studentFee.feeHead', 'creator'])->latest('fine_date'),
            'feeLedgerEntries' => fn ($query) => $query->with(['studentFee.feeHead', 'creator'])->latest('entry_date')->latest(),
            'marks.exam',
            'marks.subject',
            'documents',
        ]);

        $fees = $student->fees;
        $fines = $student->fines;
        $unpaidFines = $fines->where('status', 'unpaid')->sum('amount');
        $unlinkedUnpaidFines = $fines->where('status', 'unpaid')->whereNull('student_fee_id')->sum('amount');
        $feeBalance = $fees->sum(fn ($fee) => $fee->balance());

        return view('school.students.show', [
            'student' => $student,
            'parentOptions' => Guardian::with('user')->forSchool(SchoolContext::id())->orderBy('name')->get(),
            'feeSummary' => [
                'assigned' => $fees->sum(fn ($fee) => $fee->payableAmount()),
                'paid' => $fees->sum('paid_amount'),
                'balance' => $feeBalance,
                'discount' => $fees->sum('discount'),
                'fine' => $fees->sum('fine'),
                'arrears' => $fees->sum('arrears'),
                'overdue' => $fees->where('status', 'overdue')->count(),
                'open' => $fees->whereIn('status', ['unpaid', 'partial', 'overdue'])->count(),
                'unpaidFines' => $unpaidFines,
                'outstanding' => $feeBalance + $unlinkedUnpaidFines,
            ],
            'feePayments' => $fees->flatMap->payments->sortByDesc('paid_on')->values(),
            'fineTypes' => StudentFine::TYPES,
            'discountTypes' => StudentDiscount::TYPES,
            'ledgerTypes' => StudentFeeLedgerEntry::TYPES,
        ]);
    }

    public function edit(Student $student): View
    {
        $this->authorizeStudent($student);

        return view('school.students.form', $this->formData($student));
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $this->authorizeStudent($student);
        $validated = $this->validated($request, $student);
        $this->validateParentLogin($request);

        DB::transaction(function () use ($request, $student, $validated) {
            if ($request->hasFile('photo')) {
                $validated['photo_path'] = $request->file('photo')->store('students/'.SchoolContext::id(), 'public');
            }

            $student->update($validated);
            $this->syncGuardian($student, $request);
        });

        Activity::log('student_updated', 'Student updated: '.$student->name, ['student_id' => $student->id]);

        return redirect()->route('students.show', $student)->with('status', 'Student updated.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        $this->authorizeStudent($student);
        $student->update(['status' => 'inactive']);
        Activity::log('student_deactivated', 'Student marked inactive: '.$student->name);

        return redirect()->route('students.index')->with('status', 'Student marked inactive.');
    }

    public function storeDocument(Request $request, Student $student): RedirectResponse
    {
        $this->authorizeStudent($student);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $document = StudentDocument::create([
            'school_id' => SchoolContext::id(),
            'student_id' => $student->id,
            'title' => $validated['title'],
            'file_path' => $request->file('document')->store('student-documents/'.SchoolContext::id(), 'public'),
        ]);

        Activity::log('student_document_uploaded', 'Document uploaded for '.$student->name, [
            'student_id' => $student->id,
            'document_id' => $document->id,
        ]);

        return back()->with('status', 'Student document uploaded.');
    }

    public function destroyDocument(StudentDocument $document): RedirectResponse
    {
        abort_unless((int) $document->school_id === SchoolContext::id(), 404);

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        Activity::log('student_document_deleted', 'Student document deleted.', ['document_id' => $document->id]);

        return back()->with('status', 'Student document deleted.');
    }

    private function formData(?Student $student = null): array
    {
        $schoolId = SchoolContext::id();

        return [
            'student' => $student,
            'classes' => SchoolClass::forSchool($schoolId)->where('is_active', true)->orderBy('sort_order')->get(),
            'sections' => Section::forSchool($schoolId)->where('is_active', true)->orderBy('name')->get(),
        ];
    }

    private function validated(Request $request, ?Student $student = null): array
    {
        return $request->validate([
            'registration_number' => [
                'nullable',
                'string',
                'max:60',
                Rule::unique('students')->where('school_id', SchoolContext::id())->ignore($student?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'b_form' => ['nullable', 'string', 'max:60'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'school_class_id' => ['required', Rule::exists('school_classes', 'id')->where('school_id', SchoolContext::id())],
            'section_id' => ['nullable', Rule::exists('sections', 'id')->where('school_id', SchoolContext::id())],
            'roll_number' => ['nullable', 'string', 'max:30'],
            'admission_date' => ['nullable', 'date'],
            'previous_school' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'father_name' => ['nullable', 'string', 'max:255'],
            'mother_name' => ['nullable', 'string', 'max:255'],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'guardian_cnic' => ['nullable', 'string', 'max:60'],
            'guardian_phone' => ['nullable', 'string', 'max:50'],
            'guardian_whatsapp' => ['nullable', 'string', 'max:50'],
            'guardian_email' => ['nullable', 'email', 'max:255'],
            'guardian_address' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(['active', 'inactive', 'left', 'transferred', 'graduated'])],
        ]);
    }

    private function nextRegistrationNumber(): string
    {
        $count = Student::forSchool(SchoolContext::id())->count() + 1;

        return 'REG-'.now()->format('Y').'-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }

    private function syncGuardian(Student $student, Request $request): void
    {
        if (! $request->filled('guardian_name') && ! $request->filled('guardian_phone') && ! $request->filled('guardian_email')) {
            return;
        }

        $user = null;

        if ($request->boolean('create_parent_login') && $request->filled('guardian_email')) {
            $user = User::firstOrCreate(
                ['email' => $request->guardian_email],
                [
                    'school_id' => SchoolContext::id(),
                    'name' => $request->guardian_name ?: $student->name.' Parent',
                    'phone' => $request->guardian_phone,
                    'password' => Hash::make($request->parent_password),
                    'role' => User::ROLE_PARENT,
                    'is_active' => true,
                ],
            );
        }

        $guardian = $this->findOrCreateGuardianShell($request);
        $guardian->fill([
            'user_id' => $user?->id ?: $guardian->user_id,
            'name' => $request->guardian_name ?: $student->guardian_name ?: 'Guardian',
            'cnic' => $request->guardian_cnic,
            'phone' => $request->guardian_phone,
            'whatsapp' => $request->guardian_whatsapp,
            'email' => $request->guardian_email,
            'address' => $request->guardian_address,
            'status' => 'active',
        ]);
        $guardian->save();

        $guardian->students()->syncWithoutDetaching([
            $student->id => [
                'school_id' => SchoolContext::id(),
                'relationship_type' => 'guardian',
                'is_primary' => true,
            ],
        ]);
    }

    private function validateParentLogin(Request $request): void
    {
        if (! $request->boolean('create_parent_login')) {
            return;
        }

        if (! $request->filled('guardian_email')) {
            throw ValidationException::withMessages([
                'guardian_email' => 'Guardian email is required to create or link a parent login.',
            ]);
        }

        $existingUser = User::where('email', $request->guardian_email)->exists();

        if (! $existingUser && ! $request->filled('parent_password')) {
            throw ValidationException::withMessages([
                'parent_password' => 'Enter a password of at least 8 characters for the new parent login.',
            ]);
        }

        if ($request->filled('parent_password') && strlen((string) $request->parent_password) < 8) {
            throw ValidationException::withMessages([
                'parent_password' => 'Parent login password must be at least 8 characters.',
            ]);
        }
    }

    private function findOrCreateGuardianShell(Request $request): Guardian
    {
        $query = Guardian::forSchool(SchoolContext::id());

        if ($request->filled('guardian_email')) {
            $guardian = (clone $query)->where('email', $request->guardian_email)->first();
        } elseif ($request->filled('guardian_phone')) {
            $guardian = (clone $query)->where('phone', $request->guardian_phone)->first();
        } elseif ($request->filled('guardian_cnic')) {
            $guardian = (clone $query)->where('cnic', $request->guardian_cnic)->first();
        } else {
            $guardian = null;
        }

        return $guardian ?: new Guardian(['school_id' => SchoolContext::id()]);
    }

    private function authorizeStudent(Student $student): void
    {
        abort_unless((int) $student->school_id === SchoolContext::id(), 404);
    }
}
