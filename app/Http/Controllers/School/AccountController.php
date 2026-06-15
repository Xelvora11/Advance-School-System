<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\AccountTransaction;
use App\Models\ExpenseCategory;
use App\Models\IncomeCategory;
use App\Models\Payment;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentFee;
use App\Models\StudentFine;
use App\Models\Teacher;
use App\Models\TeacherSalaryPayment;
use App\Models\TeacherSalaryPaymentEntry;
use App\Support\Activity;
use App\Support\SchoolContext;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(Request $request): View
    {
        $schoolId = SchoolContext::id();
        $this->ensureCategories($schoolId);
        [$monthStart, $monthEnd, $monthValue] = $this->monthRange($request);

        $monthTransactions = AccountTransaction::forSchool($schoolId)
            ->whereBetween('transaction_date', [$monthStart->toDateString(), $monthEnd->toDateString()]);

        $openFees = StudentFee::forSchool($schoolId)
            ->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->get();

        $unlinkedFines = StudentFine::forSchool($schoolId)
            ->where('status', 'unpaid')
            ->whereNull('student_fee_id')
            ->sum('amount');

        $totalIncome = (float) (clone $monthTransactions)->sum('income_amount');
        $totalExpenses = (float) (clone $monthTransactions)->sum('expense_amount');

        return view('school.accounts.index', [
            'monthValue' => $monthValue,
            'summary' => [
                'totalIncome' => $totalIncome,
                'totalExpenses' => $totalExpenses,
                'feeCollection' => (float) (clone $monthTransactions)
                    ->where('type', AccountTransaction::TYPE_INCOME)
                    ->where('reference_type', Payment::class)
                    ->sum('income_amount'),
                'salaryExpenses' => (float) (clone $monthTransactions)
                    ->where('type', AccountTransaction::TYPE_EXPENSE)
                    ->where('reference_type', TeacherSalaryPaymentEntry::class)
                    ->sum('expense_amount'),
                'cashBalance' => AccountTransaction::currentBalance($schoolId),
                'outstandingFees' => $openFees->sum(fn (StudentFee $fee) => $fee->balance()) + (float) $unlinkedFines,
                'netIncome' => $totalIncome - $totalExpenses,
            ],
            'recentTransactions' => AccountTransaction::with('creator')
                ->forSchool($schoolId)
                ->orderByDesc('transaction_date')
                ->orderByDesc('id')
                ->take(10)
                ->get(),
        ]);
    }

    public function income(Request $request): View
    {
        $schoolId = SchoolContext::id();
        $this->ensureCategories($schoolId);

        return view('school.accounts.income', [
            'categories' => IncomeCategory::forSchool($schoolId)->where('is_active', true)->orderBy('name')->get(),
            'transactions' => $this->transactionsQuery($request, $schoolId, AccountTransaction::TYPE_INCOME)
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function storeIncome(Request $request): RedirectResponse
    {
        $schoolId = SchoolContext::id();
        $this->ensureCategories($schoolId);

        $validated = $request->validate([
            'transaction_date' => ['required', 'date'],
            'category' => ['required', Rule::exists('income_categories', 'name')->where(fn ($query) => $query->where('school_id', $schoolId)->where('is_active', true))],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $transaction = AccountTransaction::recordIncome(
            $schoolId,
            $validated['category'],
            $validated['description'],
            (float) $validated['amount'],
            $validated['transaction_date'],
            SchoolContext::user()->id,
            $validated['notes'] ?? null,
            'Manual Entry',
        );

        Activity::log('account_income_recorded', 'Manual income recorded: '.$transaction->reference_no, ['account_transaction_id' => $transaction->id]);

        return redirect()->route('accounts.income')->with('status', 'Income recorded successfully.');
    }

    public function expenses(Request $request): View
    {
        $schoolId = SchoolContext::id();
        $this->ensureCategories($schoolId);

        return view('school.accounts.expenses', [
            'categories' => ExpenseCategory::forSchool($schoolId)->where('is_active', true)->orderBy('name')->get(),
            'transactions' => $this->transactionsQuery($request, $schoolId, AccountTransaction::TYPE_EXPENSE)
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function storeExpense(Request $request): RedirectResponse
    {
        $schoolId = SchoolContext::id();
        $this->ensureCategories($schoolId);

        $validated = $request->validate([
            'transaction_date' => ['required', 'date'],
            'category' => ['required', Rule::exists('expense_categories', 'name')->where(fn ($query) => $query->where('school_id', $schoolId)->where('is_active', true))],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $transaction = AccountTransaction::recordExpense(
            $schoolId,
            $validated['category'],
            $validated['description'],
            (float) $validated['amount'],
            $validated['transaction_date'],
            SchoolContext::user()->id,
            $validated['notes'] ?? null,
            'Manual Entry',
        );

        Activity::log('account_expense_recorded', 'Manual expense recorded: '.$transaction->reference_no, ['account_transaction_id' => $transaction->id]);

        return redirect()->route('accounts.expenses')->with('status', 'Expense recorded successfully.');
    }

    public function ledger(Request $request): View
    {
        $schoolId = SchoolContext::id();

        return view('school.accounts.ledger', [
            'transactions' => $this->transactionsQuery($request, $schoolId)
                ->paginate(25)
                ->withQueryString(),
            'categories' => AccountTransaction::forSchool($schoolId)
                ->select('category')
                ->distinct()
                ->orderBy('category')
                ->pluck('category'),
        ]);
    }

    public function cashBook(Request $request): View
    {
        $schoolId = SchoolContext::id();
        [$period, $start, $end, $label, $periodValue] = $this->cashPeriod($request);

        $openingBalance = (float) (AccountTransaction::forSchool($schoolId)
            ->whereDate('transaction_date', '<', $start->toDateString())
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->value('balance') ?? 0);

        $periodTransactions = AccountTransaction::forSchool($schoolId)
            ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()]);

        $totalIncome = (float) (clone $periodTransactions)->sum('income_amount');
        $totalExpenses = (float) (clone $periodTransactions)->sum('expense_amount');

        return view('school.accounts.cash-book', [
            'period' => $period,
            'periodValue' => $periodValue,
            'periodLabel' => $label,
            'openingBalance' => $openingBalance,
            'totalIncome' => $totalIncome,
            'totalExpenses' => $totalExpenses,
            'closingBalance' => $openingBalance + $totalIncome - $totalExpenses,
            'transactions' => $periodTransactions
                ->orderBy('transaction_date')
                ->orderBy('id')
                ->paginate(25)
                ->withQueryString(),
        ]);
    }

    public function studentDues(Request $request): View
    {
        $schoolId = SchoolContext::id();
        $students = $this->studentsForDues($request, $schoolId)
            ->paginate(20)
            ->withQueryString();

        $students->setCollection($students->getCollection()->map(fn (Student $student) => $this->studentDueRow($student)));

        return view('school.accounts.student-dues', [
            'students' => $students,
            'classes' => SchoolClass::forSchool($schoolId)->orderBy('sort_order')->get(),
            'sections' => Section::with('schoolClass')->forSchool($schoolId)->orderBy('name')->get(),
        ]);
    }

    public function salaryReport(Request $request): View
    {
        $schoolId = SchoolContext::id();

        return view('school.accounts.salary-report', [
            'salaryRecords' => $this->salaryReportQuery($request, $schoolId)->paginate(20)->withQueryString(),
            'teachers' => Teacher::forSchool($schoolId)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function summary(Request $request): View
    {
        $schoolId = SchoolContext::id();
        [$monthStart, $monthEnd, $monthValue] = $this->monthRange($request);

        $transactions = AccountTransaction::forSchool($schoolId)
            ->whereBetween('transaction_date', [$monthStart->toDateString(), $monthEnd->toDateString()]);

        $incomeTotal = (float) (clone $transactions)->sum('income_amount');
        $expenseTotal = (float) (clone $transactions)->sum('expense_amount');

        return view('school.accounts.summary', [
            'monthValue' => $monthValue,
            'monthLabel' => $monthStart->format('F Y'),
            'incomeTotal' => $incomeTotal,
            'expenseTotal' => $expenseTotal,
            'netIncome' => $incomeTotal - $expenseTotal,
            'incomeByCategory' => (clone $transactions)
                ->where('type', AccountTransaction::TYPE_INCOME)
                ->selectRaw('category, sum(income_amount) as total')
                ->groupBy('category')
                ->orderByDesc('total')
                ->get(),
            'expenseByCategory' => (clone $transactions)
                ->where('type', AccountTransaction::TYPE_EXPENSE)
                ->selectRaw('category, sum(expense_amount) as total')
                ->groupBy('category')
                ->orderByDesc('total')
                ->get(),
        ]);
    }

    public function incomeCsv(Request $request)
    {
        $rows = $this->transactionsQuery($request, SchoolContext::id(), AccountTransaction::TYPE_INCOME)->get();

        return $this->csvResponse('account-income.csv', ['Date', 'Reference No', 'Category', 'Source', 'Description', 'Amount', 'Notes'], $rows->map(fn (AccountTransaction $transaction) => [
            optional($transaction->transaction_date)->format('Y-m-d'),
            $transaction->reference_no,
            $transaction->category,
            $transaction->source,
            $transaction->description,
            $transaction->income_amount,
            $transaction->notes,
        ]));
    }

    public function expenseCsv(Request $request)
    {
        $rows = $this->transactionsQuery($request, SchoolContext::id(), AccountTransaction::TYPE_EXPENSE)->get();

        return $this->csvResponse('account-expenses.csv', ['Date', 'Reference No', 'Category', 'Source', 'Description', 'Amount', 'Notes'], $rows->map(fn (AccountTransaction $transaction) => [
            optional($transaction->transaction_date)->format('Y-m-d'),
            $transaction->reference_no,
            $transaction->category,
            $transaction->source,
            $transaction->description,
            $transaction->expense_amount,
            $transaction->notes,
        ]));
    }

    public function ledgerCsv(Request $request)
    {
        $rows = $this->transactionsQuery($request, SchoolContext::id())->orderBy('transaction_date')->orderBy('id')->get();

        return $this->csvResponse('account-ledger.csv', ['Date', 'Reference No', 'Type', 'Category', 'Description', 'Income', 'Expense', 'Running Balance'], $rows->map(fn (AccountTransaction $transaction) => [
            optional($transaction->transaction_date)->format('Y-m-d'),
            $transaction->reference_no,
            ucfirst($transaction->type),
            $transaction->category,
            $transaction->description,
            $transaction->income_amount,
            $transaction->expense_amount,
            $transaction->balance,
        ]));
    }

    public function studentDuesCsv(Request $request)
    {
        $rows = $this->studentsForDues($request, SchoolContext::id())->get()->map(fn (Student $student) => $this->studentDueRow($student));

        return $this->csvResponse('student-dues.csv', ['Student', 'Registration', 'Class', 'Section', 'Total Assigned', 'Paid', 'Balance', 'Fine', 'Outstanding'], $rows->map(fn (array $row) => [
            $row['student'],
            $row['registration'],
            $row['class'],
            $row['section'],
            $row['total_assigned'],
            $row['paid'],
            $row['balance'],
            $row['fine'],
            $row['outstanding'],
        ]));
    }

    public function salaryReportCsv(Request $request)
    {
        $rows = $this->salaryReportQuery($request, SchoolContext::id())->get();

        return $this->csvResponse('salary-report.csv', ['Teacher', 'Assigned Salary', 'Paid', 'Remaining', 'Status'], $rows->map(fn (TeacherSalaryPayment $salary) => [
            $salary->teacher?->name,
            $salary->gross_salary,
            $salary->paid_amount,
            $salary->balance,
            ucfirst($salary->payment_status),
        ]));
    }

    public function summaryCsv(Request $request)
    {
        $schoolId = SchoolContext::id();
        [$monthStart, $monthEnd] = $this->monthRange($request);

        $transactions = AccountTransaction::forSchool($schoolId)
            ->whereBetween('transaction_date', [$monthStart->toDateString(), $monthEnd->toDateString()]);

        $incomeTotal = (float) (clone $transactions)->sum('income_amount');
        $expenseTotal = (float) (clone $transactions)->sum('expense_amount');
        $csv = $this->csvLine(['Financial Summary', $monthStart->format('F Y')]);
        $csv .= $this->csvLine(['Metric', 'Amount']);
        $csv .= $this->csvLine(['Total Income', $incomeTotal]);
        $csv .= $this->csvLine(['Total Expenses', $expenseTotal]);
        $csv .= $this->csvLine(['Net Income', $incomeTotal - $expenseTotal]);
        $csv .= "\n".$this->csvLine(['Income Category', 'Amount']);

        foreach ((clone $transactions)
            ->where('type', AccountTransaction::TYPE_INCOME)
            ->selectRaw('category, sum(income_amount) as total')
            ->groupBy('category')
            ->orderBy('category')
            ->get() as $row) {
            $csv .= $this->csvLine([$row->category, $row->total]);
        }

        $csv .= "\n".$this->csvLine(['Expense Category', 'Amount']);

        foreach ((clone $transactions)
            ->where('type', AccountTransaction::TYPE_EXPENSE)
            ->selectRaw('category, sum(expense_amount) as total')
            ->groupBy('category')
            ->orderBy('category')
            ->get() as $row) {
            $csv .= $this->csvLine([$row->category, $row->total]);
        }

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="financial-summary.csv"',
        ]);
    }

    private function ensureCategories(int $schoolId): void
    {
        IncomeCategory::ensureDefaultsForSchool($schoolId);
        ExpenseCategory::ensureDefaultsForSchool($schoolId);
    }

    private function transactionsQuery(Request $request, int $schoolId, ?string $type = null): Builder
    {
        return AccountTransaction::forSchool($schoolId)
            ->when($type, fn ($query) => $query->where('type', $type))
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->category))
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->type))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('transaction_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('transaction_date', '<=', $request->date_to))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->search);

                $query->where(function ($inner) use ($search) {
                    $inner->where('reference_no', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%')
                        ->orWhere('source', 'like', '%'.$search.'%')
                        ->orWhere('notes', 'like', '%'.$search.'%');
                });
            })
            ->orderByDesc('transaction_date')
            ->orderByDesc('id');
    }

    private function studentsForDues(Request $request, int $schoolId): Builder
    {
        return Student::with(['schoolClass', 'section', 'fees', 'fines'])
            ->forSchool($schoolId)
            ->when($request->filled('class_id'), fn ($query) => $query->where('school_class_id', $request->integer('class_id')))
            ->when($request->filled('section_id'), fn ($query) => $query->where('section_id', $request->integer('section_id')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->search);

                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', '%'.$search.'%')
                        ->orWhere('registration_number', 'like', '%'.$search.'%')
                        ->orWhere('guardian_phone', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('name');
    }

    private function salaryReportQuery(Request $request, int $schoolId): Builder
    {
        return TeacherSalaryPayment::with('teacher')
            ->forSchool($schoolId)
            ->when($request->filled('teacher_id'), fn ($query) => $query->where('teacher_id', $request->integer('teacher_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('payment_status', $request->status))
            ->when($request->filled('month'), fn ($query) => $query->where('salary_month', $request->integer('month')))
            ->when($request->filled('year'), fn ($query) => $query->where('salary_year', $request->integer('year')))
            ->latest('salary_year')
            ->latest('salary_month')
            ->latest();
    }

    private function studentDueRow(Student $student): array
    {
        $feeTotal = $student->fees->sum(fn (StudentFee $fee) => max(0, (float) $fee->amount + (float) $fee->arrears - (float) $fee->discount));
        $paid = $student->fees->sum('paid_amount');
        $balance = $student->fees->sum(fn (StudentFee $fee) => $fee->balance());
        $linkedFine = $student->fees->sum('fine');
        $unlinkedFine = $student->fines
            ->where('status', 'unpaid')
            ->whereNull('student_fee_id')
            ->sum('amount');

        return [
            'student' => $student->name,
            'registration' => $student->registration_number,
            'class' => $student->schoolClass?->name,
            'section' => $student->section?->name,
            'total_assigned' => (float) $feeTotal,
            'paid' => (float) $paid,
            'balance' => (float) $balance,
            'fine' => (float) $linkedFine + (float) $unlinkedFine,
            'outstanding' => (float) $balance + (float) $unlinkedFine,
        ];
    }

    private function monthRange(Request $request): array
    {
        $month = (string) $request->input('month', now()->format('Y-m'));

        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = now()->format('Y-m');
        }

        $start = Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfMonth();

        return [$start, $start->copy()->endOfMonth(), $month];
    }

    private function cashPeriod(Request $request): array
    {
        $period = in_array($request->input('period'), ['daily', 'monthly', 'yearly'], true)
            ? $request->input('period')
            : 'monthly';

        if ($period === 'daily') {
            $date = (string) $request->input('date', today()->toDateString());
            $start = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
                ? Carbon::parse($date)->startOfDay()
                : today()->startOfDay();

            return [$period, $start, $start->copy()->endOfDay(), $start->format('d M Y'), $start->toDateString()];
        }

        if ($period === 'yearly') {
            $year = $request->integer('year') ?: now()->year;
            $year = max(2020, min(2100, $year));
            $start = Carbon::create($year, 1, 1)->startOfYear();

            return [$period, $start, $start->copy()->endOfYear(), (string) $year, (string) $year];
        }

        $month = (string) $request->input('month', now()->format('Y-m'));

        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = now()->format('Y-m');
        }

        $start = Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfMonth();

        return [$period, $start, $start->copy()->endOfMonth(), $start->format('F Y'), $month];
    }

    private function csvResponse(string $filename, array $header, iterable $rows)
    {
        $csv = $this->csvLine($header);

        foreach ($rows as $row) {
            $csv .= $this->csvLine($row);
        }

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function csvLine(array $values): string
    {
        return implode(',', array_map(fn ($value) => '"'.str_replace('"', '""', (string) $value).'"', $values))."\n";
    }
}
