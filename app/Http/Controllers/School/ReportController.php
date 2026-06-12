<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Payment;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentDiscount;
use App\Models\StudentFee;
use App\Models\StudentFeeLedgerEntry;
use App\Models\StudentFine;
use App\Models\Teacher;
use App\Models\TeacherSalaryPayment;
use App\Support\SchoolContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $schoolId = SchoolContext::id();
        $defaultersQuery = $this->defaultersQuery($request, $schoolId);
        $paymentsQuery = $this->paymentsQuery($request, $schoolId);
        $finesQuery = $this->finesQuery($request, $schoolId);
        $discountsQuery = $this->discountsQuery($request, $schoolId);
        $salariesQuery = $this->salariesQuery($request, $schoolId);
        $defaulterRows = (clone $defaultersQuery)->get();
        $paymentRows = (clone $paymentsQuery)->get();
        $fineRows = (clone $finesQuery)->get();
        $discountRows = (clone $discountsQuery)->get();
        $salaryRows = (clone $salariesQuery)->get();

        return view('school.reports.index', [
            'classes' => SchoolClass::forSchool($schoolId)->orderBy('sort_order')->get(),
            'sections' => Section::with('schoolClass')->forSchool($schoolId)->orderBy('name')->get(),
            'students' => Student::forSchool($schoolId)->orderBy('name')->get(['id', 'name', 'registration_number']),
            'teachers' => Teacher::forSchool($schoolId)->orderBy('name')->get(['id', 'name']),
            'studentCount' => Student::forSchool($schoolId)->count(),
            'teacherCount' => Teacher::forSchool($schoolId)->count(),
            'defaulters' => $defaulterRows->take(50),
            'payments' => $paymentRows->take(50),
            'studentFines' => $fineRows->take(50),
            'studentDiscounts' => $discountRows->take(50),
            'salaryRecords' => $salaryRows->take(50),
            'pendingBalance' => $defaulterRows->sum(fn (StudentFee $fee) => $fee->balance()),
            'paymentTotal' => $paymentRows->sum('amount'),
            'fineTotal' => $fineRows->where('status', 'unpaid')->sum('amount'),
            'discountTotal' => $discountRows->sum('amount'),
            'salaryDueTotal' => $salaryRows->whereIn('payment_status', ['unpaid', 'partial'])->sum('balance'),
            'attendanceSummary' => AttendanceRecord::forSchool($schoolId)
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
        ]);
    }

    public function studentsCsv()
    {
        $rows = Student::with(['schoolClass', 'section'])->forSchool(SchoolContext::id())->orderBy('name')->get();
        $csv = "Registration,Name,Class,Section,Guardian Phone,Status\n";

        foreach ($rows as $student) {
            $csv .= implode(',', array_map(fn ($value) => '"'.str_replace('"', '""', (string) $value).'"', [
                $student->registration_number,
                $student->name,
                $student->schoolClass?->name,
                $student->section?->name,
                $student->guardian_phone,
                $student->status,
            ]))."\n";
        }

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="students.csv"',
        ]);
    }

    public function feeDefaultersCsv(Request $request)
    {
        $rows = $this->defaultersQuery($request, SchoolContext::id())->get();
        $csv = "Registration,Student,Class,Section,Fee Head,Month,Year,Due Date,Status,Amount,Fine,Discount,Paid,Balance\n";

        foreach ($rows as $fee) {
            $csv .= $this->csvLine([
                $fee->student?->registration_number,
                $fee->student?->name,
                $fee->student?->schoolClass?->name,
                $fee->student?->section?->name,
                $fee->feeHead?->name,
                $fee->month,
                $fee->year,
                optional($fee->due_date)->format('Y-m-d'),
                $fee->status,
                $fee->amount,
                $fee->fine,
                $fee->discount,
                $fee->paid_amount,
                $fee->balance(),
            ]);
        }

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="fee-defaulters.csv"',
        ]);
    }

    public function paymentsCsv(Request $request)
    {
        $rows = $this->paymentsQuery($request, SchoolContext::id())->get();
        $csv = "Receipt,Date,Student,Registration,Class,Section,Fee Head,Method,Reference,Received By,Amount,Note\n";

        foreach ($rows as $payment) {
            $csv .= $this->csvLine([
                $payment->receipt_number,
                optional($payment->paid_on)->format('Y-m-d'),
                $payment->student?->name,
                $payment->student?->registration_number,
                $payment->student?->schoolClass?->name,
                $payment->student?->section?->name,
                $payment->studentFee?->feeHead?->name,
                $payment->method,
                $payment->reference_number,
                $payment->receiver?->name,
                $payment->amount,
                $payment->note,
            ]);
        }

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="payments.csv"',
        ]);
    }

    public function studentFinesCsv(Request $request)
    {
        $rows = $this->finesQuery($request, SchoolContext::id())->get();
        $csv = "Student,Registration,Class,Section,Fine Type,Title,Amount,Fine Date,Due Date,Status,Linked Fee,Created By,Note\n";

        foreach ($rows as $fine) {
            $csv .= $this->csvLine([
                $fine->student?->name,
                $fine->student?->registration_number,
                $fine->student?->schoolClass?->name,
                $fine->student?->section?->name,
                StudentFine::TYPES[$fine->fine_type] ?? $fine->fine_type,
                $fine->title,
                $fine->amount,
                optional($fine->fine_date)->format('Y-m-d'),
                optional($fine->due_date)->format('Y-m-d'),
                $fine->status,
                $fine->studentFee?->feeHead?->name,
                $fine->creator?->name,
                $fine->note,
            ]);
        }

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="student-fines.csv"',
        ]);
    }

    public function studentDiscountsCsv(Request $request)
    {
        $rows = $this->discountsQuery($request, SchoolContext::id())->get();
        $csv = "Student,Registration,Class,Section,Discount Type,Reason,Amount,Date,Approved By,Linked Fee,Created By,Note\n";

        foreach ($rows as $discount) {
            $csv .= $this->csvLine([
                $discount->student?->name,
                $discount->student?->registration_number,
                $discount->student?->schoolClass?->name,
                $discount->student?->section?->name,
                StudentDiscount::TYPES[$discount->discount_type] ?? $discount->discount_type,
                $discount->reason,
                $discount->amount,
                optional($discount->discount_date)->format('Y-m-d'),
                $discount->approved_by,
                $discount->studentFee?->feeHead?->name,
                $discount->creator?->name,
                $discount->note,
            ]);
        }

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="student-discounts.csv"',
        ]);
    }

    public function studentLedgerCsv(Request $request)
    {
        $rows = $this->ledgerQuery($request, SchoolContext::id())->get();
        $csv = "Date,Student,Registration,Class,Section,Type,Description,Debit,Credit,Balance After,Created By\n";

        foreach ($rows as $entry) {
            $csv .= $this->csvLine([
                optional($entry->entry_date)->format('Y-m-d'),
                $entry->student?->name,
                $entry->student?->registration_number,
                $entry->student?->schoolClass?->name,
                $entry->student?->section?->name,
                StudentFeeLedgerEntry::TYPES[$entry->type] ?? $entry->type,
                $entry->description,
                $entry->debit,
                $entry->credit,
                $entry->balance_after,
                $entry->creator?->name,
            ]);
        }

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="student-ledger.csv"',
        ]);
    }

    public function teacherSalariesCsv(Request $request)
    {
        $rows = $this->salariesQuery($request, SchoolContext::id())->get();
        $csv = "Teacher,Month,Year,Gross Salary,Deductions,Deduction Reason,Paid Amount,Balance,Status,Payment Method,Payment Date,Created By,Note\n";

        foreach ($rows as $salary) {
            $csv .= $this->csvLine([
                $salary->teacher?->name,
                $salary->salary_month,
                $salary->salary_year,
                $salary->gross_salary,
                $salary->deductions,
                $salary->deduction_reason,
                $salary->paid_amount,
                $salary->balance,
                $salary->payment_status,
                $salary->payment_method,
                optional($salary->payment_date)->format('Y-m-d'),
                $salary->creator?->name,
                $salary->note,
            ]);
        }

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="teacher-salaries.csv"',
        ]);
    }

    private function defaultersQuery(Request $request, int $schoolId)
    {
        return StudentFee::with(['student.schoolClass', 'student.section', 'feeHead'])
            ->forSchool($schoolId)
            ->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->when($request->filled('status') && in_array($request->status, ['unpaid', 'partial', 'overdue'], true), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('month'), fn ($query) => $query->where('month', $request->integer('month')))
            ->when($request->filled('year'), fn ($query) => $query->where('year', $request->integer('year')))
            ->when($request->filled('class_id'), fn ($query) => $query->whereHas('student', fn ($student) => $student->where('school_class_id', $request->integer('class_id'))))
            ->when($request->filled('section_id'), fn ($query) => $query->whereHas('student', fn ($student) => $student->where('section_id', $request->integer('section_id'))))
            ->orderBy('due_date')
            ->latest();
    }

    private function paymentsQuery(Request $request, int $schoolId)
    {
        return Payment::with(['student.schoolClass', 'student.section', 'studentFee.feeHead', 'receiver'])
            ->forSchool($schoolId)
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('paid_on', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('paid_on', '<=', $request->date('date_to')))
            ->when($request->filled('month'), fn ($query) => $query->whereMonth('paid_on', $request->integer('month')))
            ->when($request->filled('year'), fn ($query) => $query->whereYear('paid_on', $request->integer('year')))
            ->when($request->filled('method'), fn ($query) => $query->where('method', $request->method))
            ->when($request->filled('class_id'), fn ($query) => $query->whereHas('student', fn ($student) => $student->where('school_class_id', $request->integer('class_id'))))
            ->when($request->filled('section_id'), fn ($query) => $query->whereHas('student', fn ($student) => $student->where('section_id', $request->integer('section_id'))))
            ->latest('paid_on')
            ->latest();
    }

    private function finesQuery(Request $request, int $schoolId)
    {
        return StudentFine::with(['student.schoolClass', 'student.section', 'studentFee.feeHead', 'creator'])
            ->forSchool($schoolId)
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('student_id'), fn ($query) => $query->where('student_id', $request->integer('student_id')))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('fine_date', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('fine_date', '<=', $request->date('date_to')))
            ->when($request->filled('month'), fn ($query) => $query->whereMonth('fine_date', $request->integer('month')))
            ->when($request->filled('year'), fn ($query) => $query->whereYear('fine_date', $request->integer('year')))
            ->when($request->filled('class_id'), fn ($query) => $query->whereHas('student', fn ($student) => $student->where('school_class_id', $request->integer('class_id'))))
            ->when($request->filled('section_id'), fn ($query) => $query->whereHas('student', fn ($student) => $student->where('section_id', $request->integer('section_id'))))
            ->latest('fine_date')
            ->latest();
    }

    private function discountsQuery(Request $request, int $schoolId)
    {
        return StudentDiscount::with(['student.schoolClass', 'student.section', 'studentFee.feeHead', 'creator'])
            ->forSchool($schoolId)
            ->when($request->filled('student_id'), fn ($query) => $query->where('student_id', $request->integer('student_id')))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('discount_date', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('discount_date', '<=', $request->date('date_to')))
            ->when($request->filled('month'), fn ($query) => $query->whereMonth('discount_date', $request->integer('month')))
            ->when($request->filled('year'), fn ($query) => $query->whereYear('discount_date', $request->integer('year')))
            ->when($request->filled('class_id'), fn ($query) => $query->whereHas('student', fn ($student) => $student->where('school_class_id', $request->integer('class_id'))))
            ->when($request->filled('section_id'), fn ($query) => $query->whereHas('student', fn ($student) => $student->where('section_id', $request->integer('section_id'))))
            ->latest('discount_date')
            ->latest();
    }

    private function ledgerQuery(Request $request, int $schoolId)
    {
        return StudentFeeLedgerEntry::with(['student.schoolClass', 'student.section', 'studentFee.feeHead', 'creator'])
            ->forSchool($schoolId)
            ->when($request->filled('student_id'), fn ($query) => $query->where('student_id', $request->integer('student_id')))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('entry_date', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('entry_date', '<=', $request->date('date_to')))
            ->when($request->filled('month'), fn ($query) => $query->whereMonth('entry_date', $request->integer('month')))
            ->when($request->filled('year'), fn ($query) => $query->whereYear('entry_date', $request->integer('year')))
            ->when($request->filled('class_id'), fn ($query) => $query->whereHas('student', fn ($student) => $student->where('school_class_id', $request->integer('class_id'))))
            ->when($request->filled('section_id'), fn ($query) => $query->whereHas('student', fn ($student) => $student->where('section_id', $request->integer('section_id'))))
            ->latest('entry_date')
            ->latest();
    }

    private function salariesQuery(Request $request, int $schoolId)
    {
        return TeacherSalaryPayment::with(['teacher', 'creator'])
            ->forSchool($schoolId)
            ->when($request->filled('salary_status'), fn ($query) => $query->where('payment_status', $request->salary_status))
            ->when($request->filled('teacher_id'), fn ($query) => $query->where('teacher_id', $request->integer('teacher_id')))
            ->when($request->filled('month'), fn ($query) => $query->where('salary_month', $request->integer('month')))
            ->when($request->filled('year'), fn ($query) => $query->where('salary_year', $request->integer('year')))
            ->latest('salary_year')
            ->latest('salary_month');
    }

    private function csvLine(array $values): string
    {
        return implode(',', array_map(fn ($value) => '"'.str_replace('"', '""', (string) $value).'"', $values))."\n";
    }
}
