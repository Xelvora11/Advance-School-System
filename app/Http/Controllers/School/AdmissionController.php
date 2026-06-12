<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Support\Activity;
use App\Support\SchoolContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdmissionController extends Controller
{
    public function index(Request $request): View
    {
        $query = Admission::with(['requestedClass'])
            ->forSchool(SchoolContext::id())
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where(function ($inner) use ($request) {
                $inner->where('student_name', 'like', '%'.$request->search.'%')
                    ->orWhere('guardian_name', 'like', '%'.$request->search.'%')
                    ->orWhere('guardian_phone', 'like', '%'.$request->search.'%');
            });
        }

        return view('school.admissions.index', [
            'admissions' => $query->paginate(15)->withQueryString(),
            'classes' => SchoolClass::forSchool(SchoolContext::id())->where('is_active', true)->orderBy('sort_order')->get(),
            'sections' => Section::with('schoolClass')->forSchool(SchoolContext::id())->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        $admission = Admission::create($validated + [
            'school_id' => SchoolContext::id(),
            'status' => 'inquiry',
            'document_checklist' => $this->documentChecklist($request),
        ]);

        Activity::log('admission_inquiry_created', 'Admission inquiry created: '.$admission->student_name, ['admission_id' => $admission->id]);

        return back()->with('status', 'Admission inquiry added.');
    }

    public function update(Request $request, Admission $admission): RedirectResponse
    {
        $this->authorizeAdmission($admission);

        $admission->update($this->validated($request) + [
            'document_checklist' => $this->documentChecklist($request),
        ]);

        Activity::log('admission_inquiry_updated', 'Admission inquiry updated: '.$admission->student_name, ['admission_id' => $admission->id]);

        return back()->with('status', 'Admission inquiry updated.');
    }

    public function approve(Admission $admission): RedirectResponse
    {
        $this->authorizeAdmission($admission);
        $admission->update(['status' => 'approved']);
        Activity::log('admission_approved', 'Admission approved: '.$admission->student_name, ['admission_id' => $admission->id]);

        return back()->with('status', 'Admission approved.');
    }

    public function reject(Request $request, Admission $admission): RedirectResponse
    {
        $this->authorizeAdmission($admission);
        $admission->update([
            'status' => 'rejected',
            'test_notes' => trim(($admission->test_notes ? $admission->test_notes."\n" : '').($request->reason ?: 'Rejected')),
        ]);
        Activity::log('admission_rejected', 'Admission rejected: '.$admission->student_name, ['admission_id' => $admission->id]);

        return back()->with('status', 'Admission rejected.');
    }

    public function convert(Request $request, Admission $admission): RedirectResponse
    {
        $this->authorizeAdmission($admission);

        if ($admission->converted_student_id || $admission->status === 'enrolled') {
            return back()->withErrors(['admission' => 'This admission has already been converted to a student.']);
        }

        if ($admission->status !== 'approved') {
            return back()->withErrors(['admission' => 'Only approved admissions can be converted to students.']);
        }

        $validated = $request->validate([
            'section_id' => ['nullable', Rule::exists('sections', 'id')->where('school_id', SchoolContext::id())],
            'roll_number' => ['nullable', 'string', 'max:50'],
        ]);

        if (! empty($validated['section_id'])) {
            $sectionBelongsToClass = Section::forSchool(SchoolContext::id())
                ->where('id', $validated['section_id'])
                ->where('school_class_id', $admission->requested_class_id)
                ->exists();

            if (! $sectionBelongsToClass) {
                return back()->withErrors(['section_id' => 'Selected section does not belong to the requested class.']);
            }
        }

        $student = DB::transaction(function () use ($admission, $validated) {
            $student = Student::create([
                'school_id' => SchoolContext::id(),
                'school_class_id' => $admission->requested_class_id,
                'section_id' => $validated['section_id'] ?? null,
                'registration_number' => $this->nextRegistrationNumber(),
                'roll_number' => $validated['roll_number'] ?? null,
                'name' => $admission->student_name,
                'date_of_birth' => $admission->date_of_birth,
                'gender' => $admission->gender,
                'admission_date' => now()->toDateString(),
                'address' => $admission->address,
                'guardian_name' => $admission->guardian_name,
                'guardian_phone' => $admission->guardian_phone,
                'status' => 'active',
            ]);

            $admission->update([
                'status' => 'enrolled',
                'converted_student_id' => $student->id,
            ]);

            return $student;
        });

        Activity::log('admission_converted', 'Admission converted to student: '.$student->name, [
            'admission_id' => $admission->id,
            'student_id' => $student->id,
        ]);

        return redirect()->route('students.show', $student)->with('status', 'Admission converted to student.');
    }

    public function form(Admission $admission)
    {
        $this->authorizeAdmission($admission);

        $data = [
            'school' => SchoolContext::school(),
            'admission' => $admission->load('requestedClass'),
            'pdf' => request()->boolean('download'),
        ];

        if ($data['pdf']) {
            return Pdf::loadView('pdf.admission-form', $data)->download('admission-form-'.$admission->id.'.pdf');
        }

        return view('pdf.admission-form', $data);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'student_name' => ['required', 'string', 'max:255'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'date_of_birth' => ['nullable', 'date'],
            'requested_class_id' => ['required', Rule::exists('school_classes', 'id')->where('school_id', SchoolContext::id())],
            'guardian_name' => ['required', 'string', 'max:255'],
            'guardian_phone' => ['required', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'test_notes' => ['nullable', 'string', 'max:3000'],
        ]);
    }

    private function documentChecklist(Request $request): array
    {
        return [
            'b_form' => $request->boolean('document_b_form'),
            'photos' => $request->boolean('document_photos'),
            'previous_result' => $request->boolean('document_previous_result'),
            'guardian_cnic' => $request->boolean('document_guardian_cnic'),
        ];
    }

    private function nextRegistrationNumber(): string
    {
        $count = Student::forSchool(SchoolContext::id())->count() + 1;

        return 'REG-'.now()->format('Y').'-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }

    private function authorizeAdmission(Admission $admission): void
    {
        abort_unless((int) $admission->school_id === SchoolContext::id(), 404);
    }
}
