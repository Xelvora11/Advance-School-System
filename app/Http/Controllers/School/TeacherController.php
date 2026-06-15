<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\AccountTransaction;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\TeacherSalaryDeduction;
use App\Models\TeacherSalaryPayment;
use App\Models\TeacherSalaryPaymentEntry;
use App\Models\User;
use App\Support\Activity;
use App\Support\SchoolContext;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TeacherController extends Controller
{
    public function index(): View
    {
        return view('school.teachers.index', [
            'teachers' => Teacher::with('assignments.schoolClass', 'assignments.section', 'assignments.subject', 'salaryPayments')
                ->forSchool(SchoolContext::id())
                ->latest()
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('school.teachers.form', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        $teacher = DB::transaction(function () use ($request, $validated) {
            $user = null;

            if ($request->boolean('create_login') && $request->filled('email')) {
                $user = User::create([
                    'school_id' => SchoolContext::id(),
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone'] ?? null,
                    'password' => Hash::make($request->login_password ?: 'password'),
                    'role' => User::ROLE_TEACHER,
                    'is_active' => true,
                ]);
            }

            $teacher = Teacher::create($validated + ['school_id' => SchoolContext::id(), 'user_id' => $user?->id]);
            $this->storeAssignment($teacher, $request);

            return $teacher;
        });

        Activity::log('teacher_created', 'Teacher created: '.$teacher->name, ['teacher_id' => $teacher->id]);

        return redirect()->route('teachers.show', $teacher)->with('status', 'Teacher added.');
    }

    public function show(Teacher $teacher): View
    {
        $this->authorizeTeacher($teacher);

        return view('school.teachers.show', [
            'teacher' => $teacher->load([
                'assignments.schoolClass',
                'assignments.section',
                'assignments.subject',
                'user',
                'salaryPayments' => fn ($query) => $query
                    ->with(['creator', 'paymentEntries.creator', 'deductionEntries.creator'])
                    ->latest('salary_year')
                    ->latest('salary_month'),
            ]),
        ]);
    }

    public function edit(Teacher $teacher): View
    {
        $this->authorizeTeacher($teacher);

        return view('school.teachers.form', $this->formData($teacher));
    }

    public function update(Request $request, Teacher $teacher): RedirectResponse
    {
        $this->authorizeTeacher($teacher);
        $oldBasicSalary = (float) $teacher->basic_salary;
        $validated = $this->validated($request, $teacher);

        DB::transaction(function () use ($request, $teacher, $validated) {
            $teacher->update($validated);
            $this->storeAssignment($teacher, $request);

            if ($teacher->user) {
                $teacher->user->update([
                    'name' => $validated['name'],
                    'phone' => $validated['phone'] ?? null,
                    'is_active' => $validated['status'] === 'active',
                ]);
            }
        });

        Activity::log('teacher_updated', 'Teacher updated: '.$teacher->name, ['teacher_id' => $teacher->id]);

        if ($oldBasicSalary !== (float) ($validated['basic_salary'] ?? 0)) {
            Activity::log('teacher_basic_salary_updated', 'Teacher basic salary updated: '.$teacher->name, [
                'teacher_id' => $teacher->id,
                'old_salary' => $oldBasicSalary,
                'new_salary' => (float) ($validated['basic_salary'] ?? 0),
            ]);
        }

        return redirect()->route('teachers.show', $teacher)->with('status', 'Teacher updated.');
    }

    public function destroy(Teacher $teacher): RedirectResponse
    {
        $this->authorizeTeacher($teacher);
        $teacher->update(['status' => 'inactive']);
        $teacher->user?->update(['is_active' => false]);
        Activity::log('teacher_deactivated', 'Teacher deactivated: '.$teacher->name);

        return redirect()->route('teachers.index')->with('status', 'Teacher deactivated.');
    }

    public function toggleStatus(Teacher $teacher): RedirectResponse
    {
        $this->authorizeTeacher($teacher);

        $teacher->update(['status' => $teacher->status === 'active' ? 'inactive' : 'active']);
        $teacher->user?->update(['is_active' => $teacher->status === 'active']);

        Activity::log('teacher_status_updated', 'Teacher status updated: '.$teacher->name, [
            'teacher_id' => $teacher->id,
            'status' => $teacher->status,
        ]);

        return back()->with('status', 'Teacher status updated.');
    }

    public function resetPassword(Request $request, Teacher $teacher): RedirectResponse
    {
        $this->authorizeTeacher($teacher);

        if (! $teacher->user) {
            return back()->withErrors(['teacher' => 'This teacher does not have a login account yet.']);
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8'],
        ]);

        $teacher->user->update(['password' => Hash::make($validated['password'])]);
        Activity::log('teacher_password_reset', 'Teacher login password reset: '.$teacher->name, ['teacher_id' => $teacher->id]);

        return back()->with('status', 'Teacher login password reset.');
    }

    public function salaries(Request $request): View
    {
        $schoolId = SchoolContext::id();
        $salaryMonth = $request->filled('month') ? $request->integer('month') : now()->month;
        $salaryYear = $request->filled('year') ? $request->integer('year') : now()->year;

        $baseQuery = TeacherSalaryPayment::with(['teacher', 'creator', 'paymentEntries', 'deductionEntries'])
            ->forSchool($schoolId)
            ->when($request->filled('teacher_id'), fn ($query) => $query->where('teacher_id', $request->integer('teacher_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('payment_status', $request->status))
            ->when($request->filled('payment_method'), fn ($query) => $query->where('payment_method', $request->payment_method))
            ->when($request->filled('month'), fn ($query) => $query->where('salary_month', $request->integer('month')))
            ->when($request->filled('year'), fn ($query) => $query->where('salary_year', $request->integer('year')));

        $salaryRecords = (clone $baseQuery)
            ->latest('salary_year')
            ->latest('salary_month')
            ->paginate(20)
            ->withQueryString();

        $monthlyRecords = TeacherSalaryPayment::forSchool($schoolId)
            ->where('salary_month', $salaryMonth)
            ->where('salary_year', $salaryYear)
            ->get();

        return view('school.teachers.salaries', [
            'salaryRecords' => $salaryRecords,
            'teachers' => Teacher::forSchool($schoolId)->orderBy('name')->get(),
            'summary' => [
                'totalTeachers' => Teacher::forSchool($schoolId)->where('status', 'active')->count(),
                'monthlySalaryAmount' => $monthlyRecords->sum('gross_salary'),
                'paidThisMonth' => $monthlyRecords->sum('paid_amount'),
                'pendingThisMonth' => $monthlyRecords->sum('balance'),
                'partialPayments' => $monthlyRecords->where('payment_status', 'partial')->count(),
                'totalDeductions' => $monthlyRecords->sum('deductions'),
            ],
            'salaryMonth' => $salaryMonth,
            'salaryYear' => $salaryYear,
        ]);
    }

    public function salaryShow(TeacherSalaryPayment $salaryPayment): View
    {
        $this->authorizeSalaryPayment($salaryPayment);

        return view('school.teachers.salary-show', [
            'salary' => $salaryPayment->load(['teacher', 'creator', 'paymentEntries.creator', 'deductionEntries.creator']),
        ]);
    }

    public function updateTeacherSalary(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'teacher_id' => ['required', Rule::exists('teachers', 'id')->where('school_id', SchoolContext::id())],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'salary_payment_method' => ['nullable', Rule::in(['cash', 'bank_transfer', 'easypaisa', 'jazzcash', 'other'])],
            'bank_account_note' => ['nullable', 'string', 'max:1000'],
            'salary_effective_from' => ['nullable', 'date'],
        ]);

        $teacher = Teacher::forSchool(SchoolContext::id())->findOrFail($validated['teacher_id']);
        $oldBasicSalary = (float) $teacher->basic_salary;

        $teacher->update([
            'basic_salary' => $validated['basic_salary'],
            'salary_payment_method' => $validated['salary_payment_method'] ?? null,
            'bank_account_note' => $validated['bank_account_note'] ?? null,
            'salary_effective_from' => $validated['salary_effective_from'] ?? null,
        ]);

        Activity::log('teacher_basic_salary_updated', 'Teacher basic salary assigned/updated: '.$teacher->name, [
            'teacher_id' => $teacher->id,
            'old_salary' => $oldBasicSalary,
            'new_salary' => (float) $validated['basic_salary'],
            'effective_from' => $validated['salary_effective_from'] ?? null,
        ]);

        return back()->with('status', 'Teacher salary settings updated.');
    }

    public function generateMonthlySalaries(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'salary_month' => ['required', 'integer', 'min:1', 'max:12'],
            'salary_year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'teacher_id' => ['nullable', Rule::exists('teachers', 'id')->where('school_id', SchoolContext::id())],
            'active_only' => ['nullable', 'boolean'],
        ]);

        $schoolId = SchoolContext::id();
        $teachers = Teacher::forSchool($schoolId)
            ->when($request->boolean('active_only', true), fn ($query) => $query->where('status', 'active'))
            ->when($validated['teacher_id'] ?? null, fn ($query, $teacherId) => $query->where('id', $teacherId))
            ->orderBy('name')
            ->get();

        $created = 0;
        $skipped = 0;
        $duplicateSkipped = 0;
        $missingSalarySkipped = 0;
        $totalGeneratedAmount = 0;

        DB::transaction(function () use ($teachers, $validated, $schoolId, &$created, &$duplicateSkipped, &$missingSalarySkipped, &$skipped, &$totalGeneratedAmount) {
            foreach ($teachers as $teacher) {
                $grossSalary = (float) $teacher->basic_salary;

                if ($grossSalary <= 0) {
                    $skipped++;
                    $missingSalarySkipped++;

                    continue;
                }

                $exists = TeacherSalaryPayment::forSchool($schoolId)
                    ->where('teacher_id', $teacher->id)
                    ->where('salary_month', $validated['salary_month'])
                    ->where('salary_year', $validated['salary_year'])
                    ->exists();

                if ($exists) {
                    $skipped++;
                    $duplicateSkipped++;

                    continue;
                }

                $salary = new TeacherSalaryPayment([
                    'school_id' => $schoolId,
                    'teacher_id' => $teacher->id,
                    'salary_month' => $validated['salary_month'],
                    'salary_year' => $validated['salary_year'],
                    'gross_salary' => $grossSalary,
                    'deductions' => 0,
                    'paid_amount' => 0,
                    'created_by' => SchoolContext::user()->id,
                ]);
                $salary->recalculate();
                $salary->save();
                $created++;
                $totalGeneratedAmount += $grossSalary;
            }
        });

        Activity::log('teacher_salary_bulk_generated', "Generated {$created} teacher salary records; skipped {$skipped}.", $validated + [
            'processed' => $teachers->count(),
            'created' => $created,
            'skipped' => $skipped,
            'duplicate_skipped' => $duplicateSkipped,
            'missing_salary_skipped' => $missingSalarySkipped,
            'total_generated_amount' => $totalGeneratedAmount,
        ]);

        $message = "{$teachers->count()} teachers processed. {$created} salary records generated, {$duplicateSkipped} duplicates skipped. Total PKR ".number_format($totalGeneratedAmount, 2).' generated.';

        if ($missingSalarySkipped > 0) {
            $message .= ' Some teachers were skipped because basic salary is not assigned.';
        }

        return back()->with('status', $message);
    }

    public function updateSalaryRecord(Request $request, TeacherSalaryPayment $salaryPayment): RedirectResponse
    {
        $this->authorizeSalaryPayment($salaryPayment);

        if ($salaryPayment->payment_status === 'paid') {
            throw ValidationException::withMessages([
                'salary' => 'Paid salary records cannot be edited.',
            ]);
        }

        $validated = $request->validate([
            'gross_salary' => ['required', 'numeric', 'min:0'],
            'deduction_reason' => ['nullable', 'string', 'max:1000'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        if ((float) $salaryPayment->deductions > (float) $validated['gross_salary']) {
            throw ValidationException::withMessages([
                'deductions' => 'Deductions cannot be greater than gross salary.',
            ]);
        }

        if ((float) $salaryPayment->paid_amount > ((float) $validated['gross_salary'] - (float) $salaryPayment->deductions)) {
            throw ValidationException::withMessages([
                'gross_salary' => 'Gross salary minus deductions cannot be less than already paid amount.',
            ]);
        }

        $oldStatus = $salaryPayment->payment_status;
        $salaryPayment->fill([
            'gross_salary' => $validated['gross_salary'],
            'deduction_reason' => $validated['deduction_reason'] ?? null,
            'note' => $validated['note'] ?? null,
        ]);
        $salaryPayment->recalculate();
        $salaryPayment->save();
        $this->logSalaryStatusChange($salaryPayment, $oldStatus);

        Activity::log('teacher_salary_record_updated', 'Teacher salary record updated.', ['salary_id' => $salaryPayment->id]);

        return back()->with('status', 'Salary record updated.');
    }

    public function generateSalary(Request $request, Teacher $teacher): RedirectResponse
    {
        $this->authorizeTeacher($teacher);

        $validated = $request->validate([
            'salary_month' => ['required', 'integer', 'min:1', 'max:12'],
            'salary_year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'gross_salary' => ['nullable', 'numeric', 'min:0'],
            'deductions' => ['nullable', 'numeric', 'min:0'],
            'deduction_reason' => ['nullable', 'string', 'max:1000'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $grossSalary = (float) (($validated['gross_salary'] ?? null) ?: $teacher->basic_salary);

        if ($grossSalary <= 0) {
            throw ValidationException::withMessages([
                'gross_salary' => 'Add a basic salary or enter a gross salary for this month.',
            ]);
        }

        $deductions = (float) ($validated['deductions'] ?? 0);

        if ($deductions > $grossSalary) {
            throw ValidationException::withMessages([
                'deductions' => 'Deductions cannot be greater than gross salary.',
            ]);
        }

        $exists = TeacherSalaryPayment::forSchool(SchoolContext::id())
            ->where('teacher_id', $teacher->id)
            ->where('salary_month', $validated['salary_month'])
            ->where('salary_year', $validated['salary_year'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'salary_month' => 'Salary record already exists for this teacher and month.',
            ]);
        }

        $salary = new TeacherSalaryPayment([
            'school_id' => SchoolContext::id(),
            'teacher_id' => $teacher->id,
            'salary_month' => $validated['salary_month'],
            'salary_year' => $validated['salary_year'],
            'gross_salary' => $grossSalary,
            'deductions' => $deductions,
            'deduction_reason' => $validated['deduction_reason'] ?? null,
            'paid_amount' => 0,
            'note' => $validated['note'] ?? null,
            'created_by' => SchoolContext::user()->id,
        ]);
        $salary->recalculate();
        $salary->save();

        if ($deductions > 0) {
            TeacherSalaryDeduction::create([
                'school_id' => SchoolContext::id(),
                'salary_record_id' => $salary->id,
                'teacher_id' => $teacher->id,
                'amount' => $deductions,
                'reason' => $validated['deduction_reason'] ?? 'Initial deduction',
                'note' => 'Created with salary record.',
                'created_by' => SchoolContext::user()->id,
            ]);
            $salary->refreshLedgerTotals();
            $salary->save();
        }

        Activity::log('teacher_salary_generated', 'Teacher salary record generated: '.$teacher->name, ['teacher_id' => $teacher->id, 'salary_id' => $salary->id]);

        return back()->with('status', 'Salary record generated.');
    }

    public function recordSalaryPayment(Request $request, TeacherSalaryPayment $salaryPayment): RedirectResponse
    {
        $this->authorizeSalaryPayment($salaryPayment);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', Rule::in(['cash', 'bank_transfer', 'easypaisa', 'jazzcash', 'other'])],
            'payment_date' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:120'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        if ((float) $validated['amount'] > (float) $salaryPayment->balance) {
            throw ValidationException::withMessages([
                'amount' => 'Payment amount cannot be greater than remaining salary balance.',
            ]);
        }

        DB::transaction(function () use ($salaryPayment, $validated) {
            $oldStatus = $salaryPayment->payment_status;

            $paymentEntry = TeacherSalaryPaymentEntry::create([
                'school_id' => SchoolContext::id(),
                'salary_record_id' => $salaryPayment->id,
                'teacher_id' => $salaryPayment->teacher_id,
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'payment_date' => $validated['payment_date'],
                'reference_number' => $validated['reference_number'] ?? null,
                'note' => $validated['note'] ?? null,
                'created_by' => SchoolContext::user()->id,
            ]);

            $salaryPayment->refreshLedgerTotals();
            if (! blank($validated['note'] ?? null)) {
                $salaryPayment->note = trim(($salaryPayment->note ? $salaryPayment->note."\n" : '').$validated['note']);
            }
            $salaryPayment->save();
            $this->logSalaryStatusChange($salaryPayment, $oldStatus);

            $salaryPayment->loadMissing('teacher');
            AccountTransaction::recordExpense(
                SchoolContext::id(),
                'Salaries',
                'Salary paid to '.($salaryPayment->teacher?->name ?: 'teacher').' for '.Carbon::create((int) $salaryPayment->salary_year, (int) $salaryPayment->salary_month, 1)->format('F Y'),
                (float) $validated['amount'],
                $validated['payment_date'],
                SchoolContext::user()->id,
                $validated['note'] ?? null,
                'Salary Payment',
                TeacherSalaryPaymentEntry::class,
                $paymentEntry->id,
            );
        });

        Activity::log('teacher_salary_payment_recorded', 'Teacher salary payment recorded.', ['salary_id' => $salaryPayment->id]);

        return back()->with('status', 'Salary payment recorded.');
    }

    public function recordSalaryDeduction(Request $request, TeacherSalaryPayment $salaryPayment): RedirectResponse
    {
        $this->authorizeSalaryPayment($salaryPayment);

        $validated = $request->validate([
            'deductions' => ['required', 'numeric', 'min:0.01'],
            'deduction_reason' => ['required', 'string', 'max:160'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $newDeductions = (float) $salaryPayment->deductions + (float) $validated['deductions'];

        if ($newDeductions > (float) $salaryPayment->gross_salary) {
            throw ValidationException::withMessages([
                'deductions' => 'Total deductions cannot be greater than gross salary.',
            ]);
        }

        DB::transaction(function () use ($salaryPayment, $validated) {
            $oldStatus = $salaryPayment->payment_status;

            TeacherSalaryDeduction::create([
                'school_id' => SchoolContext::id(),
                'salary_record_id' => $salaryPayment->id,
                'teacher_id' => $salaryPayment->teacher_id,
                'amount' => $validated['deductions'],
                'reason' => $validated['deduction_reason'],
                'note' => $validated['note'] ?? null,
                'created_by' => SchoolContext::user()->id,
            ]);

            $salaryPayment->refreshLedgerTotals();
            $salaryPayment->deduction_reason = trim(($salaryPayment->deduction_reason ? $salaryPayment->deduction_reason."\n" : '').$validated['deduction_reason']);
            $salaryPayment->save();
            $this->logSalaryStatusChange($salaryPayment, $oldStatus);
        });

        Activity::log('teacher_salary_deduction_added', 'Teacher salary deduction added.', ['salary_id' => $salaryPayment->id]);

        return back()->with('status', 'Salary deduction added.');
    }

    private function formData(?Teacher $teacher = null): array
    {
        $schoolId = SchoolContext::id();

        return [
            'teacher' => $teacher,
            'classes' => SchoolClass::forSchool($schoolId)->where('is_active', true)->orderBy('sort_order')->get(),
            'sections' => Section::forSchool($schoolId)->where('is_active', true)->orderBy('name')->get(),
            'subjects' => Subject::forSchool($schoolId)->where('is_active', true)->orderBy('name')->get(),
        ];
    }

    private function validated(Request $request, ?Teacher $teacher = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('teachers')->where('school_id', SchoolContext::id())->ignore($teacher?->id),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'cnic' => ['nullable', 'string', 'max:60'],
            'qualification' => ['nullable', 'string', 'max:255'],
            'joining_date' => ['nullable', 'date'],
            'basic_salary' => ['nullable', 'numeric', 'min:0'],
            'salary_payment_method' => ['nullable', Rule::in(['cash', 'bank_transfer', 'easypaisa', 'jazzcash', 'other'])],
            'bank_account_note' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'login_password' => ['nullable', 'string', 'min:8'],
        ]);
    }

    private function storeAssignment(Teacher $teacher, Request $request): void
    {
        if (! $request->filled('school_class_id')) {
            return;
        }

        $request->validate([
            'school_class_id' => ['nullable', Rule::exists('school_classes', 'id')->where('school_id', SchoolContext::id())],
            'section_id' => ['nullable', Rule::exists('sections', 'id')->where('school_id', SchoolContext::id())],
            'subject_id' => ['nullable', Rule::exists('subjects', 'id')->where('school_id', SchoolContext::id())],
        ]);

        TeacherAssignment::updateOrCreate(
            [
                'teacher_id' => $teacher->id,
                'school_class_id' => $request->integer('school_class_id'),
                'section_id' => $request->filled('section_id') ? $request->integer('section_id') : null,
                'subject_id' => $request->filled('subject_id') ? $request->integer('subject_id') : null,
            ],
            ['school_id' => SchoolContext::id()],
        );
    }

    private function authorizeTeacher(Teacher $teacher): void
    {
        abort_unless((int) $teacher->school_id === SchoolContext::id(), 404);
    }

    private function authorizeSalaryPayment(TeacherSalaryPayment $salaryPayment): void
    {
        abort_unless((int) $salaryPayment->school_id === SchoolContext::id(), 404);
    }

    private function logSalaryStatusChange(TeacherSalaryPayment $salaryPayment, ?string $oldStatus): void
    {
        if ($oldStatus === $salaryPayment->payment_status) {
            return;
        }

        Activity::log('teacher_salary_status_changed', "Salary status changed from {$oldStatus} to {$salaryPayment->payment_status}.", [
            'salary_id' => $salaryPayment->id,
            'teacher_id' => $salaryPayment->teacher_id,
        ]);
    }
}
