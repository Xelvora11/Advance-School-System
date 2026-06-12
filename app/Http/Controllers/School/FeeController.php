<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\FeeHead;
use App\Models\FeeStructure;
use App\Models\Guardian;
use App\Models\Payment;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentDiscount;
use App\Models\StudentFee;
use App\Models\StudentFeeLedgerEntry;
use App\Models\StudentFine;
use App\Support\Activity;
use App\Support\SchoolContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FeeController extends Controller
{
    public function index(Request $request): View
    {
        $schoolId = SchoolContext::id();
        $this->refreshOverdueFees($schoolId);

        $fees = StudentFee::with(['student.schoolClass', 'student.section', 'feeHead', 'payments'])
            ->forSchool($schoolId)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->search);

                $query->whereHas('student', function ($student) use ($search) {
                    $student->where('name', 'like', '%'.$search.'%')
                        ->orWhere('registration_number', 'like', '%'.$search.'%')
                        ->orWhere('guardian_phone', 'like', '%'.$search.'%');
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('month'), fn ($query) => $query->where('month', $request->integer('month')))
            ->when($request->filled('year'), fn ($query) => $query->where('year', $request->integer('year')))
            ->when($request->filled('class_id'), fn ($query) => $query->whereHas('student', fn ($student) => $student->where('school_class_id', $request->integer('class_id'))))
            ->when($request->filled('section_id'), fn ($query) => $query->whereHas('student', fn ($student) => $student->where('section_id', $request->integer('section_id'))))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $openFeeRecords = StudentFee::with(['student.schoolClass', 'student.section', 'feeHead'])
            ->forSchool($schoolId)
            ->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->latest()
            ->take(100)
            ->get();

        $defaulters = StudentFee::with(['student.schoolClass', 'student.section', 'payments'])
            ->forSchool($schoolId)
            ->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->whereDate('due_date', '<', today())
            ->latest('due_date')
            ->take(50)
            ->get();

        $thisMonthFees = StudentFee::forSchool($schoolId)
            ->where('month', now()->month)
            ->where('year', now()->year)
            ->get();

        $allOpenFees = StudentFee::forSchool($schoolId)
            ->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->get();

        $overview = [
            'totalGeneratedThisMonth' => $thisMonthFees->sum(fn (StudentFee $fee) => $fee->payableAmount()),
            'totalCollectedThisMonth' => (float) Payment::forSchool($schoolId)->whereMonth('paid_on', now()->month)->whereYear('paid_on', now()->year)->sum('amount'),
            'pendingBalance' => $allOpenFees->sum(fn (StudentFee $fee) => $fee->balance()),
            'overdueBalance' => $defaulters->sum(fn (StudentFee $fee) => $fee->balance()),
            'totalFines' => (float) StudentFine::forSchool($schoolId)->where('status', 'unpaid')->sum('amount'),
            'totalDiscounts' => (float) StudentDiscount::forSchool($schoolId)->sum('amount'),
            'todaysCollection' => (float) Payment::forSchool($schoolId)->whereDate('paid_on', today())->sum('amount'),
            'defaulterStudents' => $defaulters->pluck('student_id')->filter()->unique()->count(),
        ];

        return view('school.fees.index', [
            'feeHeads' => FeeHead::forSchool($schoolId)->orderBy('name')->get(),
            'categoryTypes' => FeeHead::CATEGORY_TYPES,
            'structures' => FeeStructure::with(['feeHead', 'schoolClass', 'section', 'student'])->forSchool($schoolId)->latest()->get(),
            'fees' => $fees,
            'classes' => SchoolClass::forSchool($schoolId)->orderBy('sort_order')->get(),
            'sections' => Section::with('schoolClass')->forSchool($schoolId)->orderBy('name')->get(),
            'students' => Student::forSchool($schoolId)->where('status', 'active')->orderBy('name')->get(),
            'openFeeRecords' => $openFeeRecords,
            'fineTypes' => StudentFine::TYPES,
            'fines' => StudentFine::with(['student.schoolClass', 'student.section', 'studentFee.feeHead'])
                ->forSchool($schoolId)
                ->latest('fine_date')
                ->take(30)
                ->get(),
            'discountTypes' => StudentDiscount::TYPES,
            'ledgerTypes' => StudentFeeLedgerEntry::TYPES,
            'discounts' => StudentDiscount::with(['student.schoolClass', 'student.section', 'studentFee.feeHead', 'creator'])
                ->forSchool($schoolId)
                ->latest('discount_date')
                ->take(30)
                ->get(),
            'payments' => Payment::with(['student.schoolClass', 'student.section', 'studentFee.feeHead', 'receiver'])
                ->forSchool($schoolId)
                ->latest('paid_on')
                ->take(30)
                ->get(),
            'ledgerEntries' => StudentFeeLedgerEntry::with(['student.schoolClass', 'student.section', 'studentFee.feeHead', 'creator'])
                ->forSchool($schoolId)
                ->latest('entry_date')
                ->latest()
                ->take(40)
                ->get(),
            'defaulters' => $defaulters,
            'overview' => $overview,
        ]);
    }

    public function studentAccount(Student $student): View
    {
        abort_unless((int) $student->school_id === SchoolContext::id(), 404);
        $this->refreshOverdueFees(SchoolContext::id());

        return view('school.fees.student-account', $this->studentAccountData($student));
    }

    public function studentPayment(Request $request, Student $student): RedirectResponse
    {
        abort_unless((int) $student->school_id === SchoolContext::id(), 404);

        $validated = $request->validate([
            'student_fee_id' => ['nullable', Rule::exists('student_fees', 'id')->where('school_id', SchoolContext::id())],
            'amount' => ['required', 'numeric', 'min:1'],
            'method' => ['required', Rule::in(['cash', 'bank_transfer', 'easypaisa', 'jazzcash', 'other'])],
            'paid_on' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:120'],
            'proof' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:4096'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $schoolId = SchoolContext::id();
        $proofPath = $request->hasFile('proof')
            ? $request->file('proof')->store('payment-proofs/'.$schoolId, 'public')
            : null;

        $payments = DB::transaction(function () use ($student, $validated, $schoolId, $proofPath) {
            $openFeesQuery = StudentFee::with('feeHead')
                ->forSchool($schoolId)
                ->where('student_id', $student->id)
                ->whereIn('status', ['unpaid', 'partial', 'overdue'])
                ->orderBy('due_date')
                ->orderBy('year')
                ->orderBy('month')
                ->orderBy('id')
                ->lockForUpdate();

            if (! empty($validated['student_fee_id'])) {
                $openFeesQuery->where('id', $validated['student_fee_id']);
            }

            $openFees = $openFeesQuery->get()->filter(fn (StudentFee $fee) => $fee->balance() > 0)->values();

            if ($openFees->isEmpty()) {
                throw ValidationException::withMessages([
                    'student_fee_id' => 'No pending balance found for this student.',
                ]);
            }

            $totalBalance = $openFees->sum(fn (StudentFee $fee) => $fee->balance());

            if ((float) $validated['amount'] > $totalBalance) {
                throw ValidationException::withMessages([
                    'amount' => 'Payment amount cannot exceed remaining balance.',
                ]);
            }

            $remaining = (float) $validated['amount'];
            $createdPayments = collect();

            foreach ($openFees as $fee) {
                if ($remaining <= 0) {
                    break;
                }

                $amountForFee = min($fee->balance(), $remaining);
                $payment = $this->createPaymentForFee($fee, $validated, $amountForFee, $proofPath);
                $createdPayments->push($payment);
                $remaining -= $amountForFee;
            }

            return $createdPayments;
        });

        $latestPayment = $payments->last();
        Activity::log('student_payment_recorded', 'Manual student payment recorded.', [
            'student_id' => $student->id,
            'amount' => $validated['amount'],
            'payment_count' => $payments->count(),
        ]);

        return redirect()
            ->route('fees.students.account', $student)
            ->with('status', 'Payment recorded successfully.')
            ->with('latest_payment_id', $latestPayment?->id);
    }

    public function studentDiscount(Request $request, Student $student): RedirectResponse
    {
        abort_unless((int) $student->school_id === SchoolContext::id(), 404);

        $validated = $request->validate([
            'student_fee_id' => ['required', Rule::exists('student_fees', 'id')->where('school_id', SchoolContext::id())],
            'discount' => ['required', 'numeric', 'min:0.01'],
            'discount_type' => ['nullable', Rule::in(array_keys(StudentDiscount::TYPES))],
            'reason' => ['nullable', 'string', 'max:160'],
            'approved_by' => ['nullable', 'string', 'max:160'],
            'discount_date' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $fee = StudentFee::forSchool(SchoolContext::id())
            ->where('student_id', $student->id)
            ->findOrFail($validated['student_fee_id']);

        DB::transaction(function () use ($fee, $validated) {
            $this->applyDiscountToFee($fee, $validated);
        });

        return redirect()
            ->route('fees.students.account', $student)
            ->with('status', 'Discount added.');
    }

    public function storeHead(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('fee_heads')->where('school_id', SchoolContext::id())],
            'code' => ['nullable', 'string', 'max:30'],
            'category_type' => ['required', Rule::in(array_keys(FeeHead::CATEGORY_TYPES))],
            'default_amount' => ['required', 'numeric', 'min:0'],
            'frequency' => ['nullable', Rule::in(['monthly', 'one_time', 'annual', 'once'])],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['frequency'] = $validated['frequency'] ?? match ($validated['category_type']) {
            'one_time_fee' => 'one_time',
            'annual_fee' => 'annual',
            default => 'monthly',
        };

        if (($validated['frequency'] ?? null) === 'once') {
            $validated['frequency'] = 'one_time';
        }

        FeeHead::create($validated + [
            'school_id' => SchoolContext::id(),
            'is_active' => $request->boolean('is_active', true),
        ]);
        Activity::log('fee_category_created', 'Fee category created: '.$validated['name']);

        return back()->with('status', 'Fee category added.');
    }

    public function storeStructure(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'fee_head_id' => ['required', Rule::exists('fee_heads', 'id')->where('school_id', SchoolContext::id())],
            'type' => ['required', Rule::in(['class', 'section', 'student'])],
            'school_class_id' => ['nullable', Rule::exists('school_classes', 'id')->where('school_id', SchoolContext::id())],
            'section_id' => ['nullable', Rule::exists('sections', 'id')->where('school_id', SchoolContext::id())],
            'student_id' => ['nullable', Rule::exists('students', 'id')->where('school_id', SchoolContext::id())],
            'amount' => ['required', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0', 'lte:amount'],
            'due_day' => ['nullable', 'integer', 'min:1', 'max:28'],
            'due_date' => ['nullable', 'date'],
            'frequency' => ['required', Rule::in(['monthly', 'one_time', 'annual'])],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($validated['type'] === 'class' && empty($validated['school_class_id'])) {
            return back()->withErrors(['school_class_id' => 'Please select a class.']);
        }

        if ($validated['type'] === 'section' && (empty($validated['school_class_id']) || empty($validated['section_id']))) {
            return back()->withErrors(['section_id' => 'Please select a class and section for section-wise fee plan.']);
        }

        if ($validated['type'] === 'student' && empty($validated['student_id'])) {
            return back()->withErrors(['student_id' => 'Please select a student.']);
        }

        if ($validated['type'] === 'class') {
            $validated['section_id'] = null;
            $validated['student_id'] = null;
        }

        if ($validated['type'] === 'section') {
            $validated['student_id'] = null;
        }

        if ($validated['type'] === 'student') {
            $validated['school_class_id'] = null;
            $validated['section_id'] = null;
        }

        FeeStructure::create($validated + [
            'school_id' => SchoolContext::id(),
            'discount' => $validated['discount'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);
        Activity::log('fee_plan_created', 'Fee plan created.');

        return back()->with('status', 'Fee plan added.');
    }

    public function generate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'fee_to_generate' => ['required', 'string'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'school_class_id' => ['nullable', Rule::exists('school_classes', 'id')->where('school_id', SchoolContext::id())],
            'section_id' => ['nullable', Rule::exists('sections', 'id')->where('school_id', SchoolContext::id())],
            'due_date' => ['required', 'date'],
            'active_only' => ['nullable', 'boolean'],
        ], [
            'fee_to_generate.required' => 'Please select which fee you want to generate.',
            'month.required' => 'Please select a valid month.',
            'month.integer' => 'Please select a valid month.',
            'month.min' => 'Please select a valid month.',
            'month.max' => 'Please select a valid month.',
            'year.required' => 'Please select a valid year.',
            'year.integer' => 'Please select a valid year.',
            'year.min' => 'Please select a valid year.',
            'year.max' => 'Please select a valid year.',
            'due_date.required' => 'Please select a due date.',
            'due_date.date' => 'Please select a due date.',
        ]);

        $schoolId = SchoolContext::id();
        $activeOnly = $request->boolean('active_only', true);
        $selectedFeeHeadId = null;
        $feeGeneratedFor = 'All Monthly Fees';

        if ($validated['fee_to_generate'] !== 'all_monthly') {
            if (! str_starts_with($validated['fee_to_generate'], 'fee_head:')) {
                throw ValidationException::withMessages([
                    'fee_to_generate' => 'Please select which fee you want to generate.',
                ]);
            }

            $selectedFeeHeadId = (int) substr($validated['fee_to_generate'], strlen('fee_head:'));
            $selectedFeeHead = FeeHead::forSchool($schoolId)
                ->where('is_active', true)
                ->find($selectedFeeHeadId);

            if (! $selectedFeeHead || ! FeeStructure::forSchool($schoolId)->where('is_active', true)->where('fee_head_id', $selectedFeeHeadId)->exists()) {
                throw ValidationException::withMessages([
                    'fee_to_generate' => 'Please select which fee you want to generate.',
                ]);
            }

            $feeGeneratedFor = $selectedFeeHead->name;
        }

        if (! empty($validated['school_class_id']) && ! empty($validated['section_id'])) {
            $sectionBelongsToClass = Section::forSchool($schoolId)
                ->whereKey($validated['section_id'])
                ->where('school_class_id', $validated['school_class_id'])
                ->exists();

            if (! $sectionBelongsToClass) {
                throw ValidationException::withMessages([
                    'section_id' => 'Please select a valid section for the selected class.',
                ]);
            }
        }

        $students = Student::forSchool($schoolId)
            ->when($activeOnly, fn ($query) => $query->where('status', 'active'))
            ->when($validated['school_class_id'] ?? null, fn ($query, $classId) => $query->where('school_class_id', $classId))
            ->when($validated['section_id'] ?? null, fn ($query, $sectionId) => $query->where('section_id', $sectionId))
            ->get();

        $created = 0;
        $skipped = 0;
        $totalAmount = 0;
        $totalPreviousBalance = 0;

        DB::transaction(function () use ($students, $validated, $schoolId, $selectedFeeHeadId, &$created, &$skipped, &$totalAmount, &$totalPreviousBalance) {
            foreach ($students as $student) {
                $structures = FeeStructure::with('feeHead')
                    ->forSchool($schoolId)
                    ->where('is_active', true)
                    ->when($selectedFeeHeadId, fn ($query) => $query->where('fee_head_id', $selectedFeeHeadId))
                    ->when(! $selectedFeeHeadId, fn ($query) => $query->where('frequency', 'monthly'))
                    ->where(function ($query) use ($student) {
                        $query->where(function ($classQuery) use ($student) {
                            $classQuery->where('type', 'class')->where('school_class_id', $student->school_class_id);
                        })->orWhere(function ($sectionQuery) use ($student) {
                            $sectionQuery->where('type', 'section')
                                ->where('school_class_id', $student->school_class_id)
                                ->where('section_id', $student->section_id);
                        })->orWhere(function ($studentQuery) use ($student) {
                            $studentQuery->where('type', 'student')->where('student_id', $student->id);
                        });
                    })
                    ->get()
                    ->sortBy(fn (FeeStructure $structure) => match ($structure->type) {
                        'student' => 0,
                        'section' => 1,
                        default => 2,
                    })
                    ->unique('fee_head_id')
                    ->values();

                $previousFees = StudentFee::forSchool($schoolId)
                    ->where('student_id', $student->id)
                    ->whereIn('status', ['unpaid', 'partial', 'overdue'])
                    ->where(function ($query) use ($validated) {
                        $query->where('year', '<', $validated['year'])
                            ->orWhere(function ($inner) use ($validated) {
                                $inner->where('year', $validated['year'])
                                    ->where('month', '<', $validated['month']);
                            });
                    })
                    ->lockForUpdate()
                    ->get();

                $previousBalance = $previousFees->sum(fn (StudentFee $fee) => $fee->balance());
                $unlinkedFines = StudentFine::forSchool($schoolId)
                    ->where('student_id', $student->id)
                    ->whereNull('student_fee_id')
                    ->where('status', 'unpaid')
                    ->lockForUpdate()
                    ->get();
                $unpaidFineBalance = $unlinkedFines->sum(fn (StudentFine $fine) => (float) $fine->amount);
                $carryForwardApplied = false;

                foreach ($structures as $structure) {
                    if (! $structure->feeHead?->is_active) {
                        continue;
                    }

                    if ($this->structureAlreadyGenerated($structure, $student, $validated['month'], $validated['year'], $schoolId)) {
                        $skipped++;

                        continue;
                    }

                    $dueDate = $validated['due_date'];
                    $arrears = $carryForwardApplied ? 0 : $previousBalance;
                    $fine = $carryForwardApplied ? 0 : $unpaidFineBalance;

                    $fee = StudentFee::firstOrCreate(
                        [
                            'school_id' => $schoolId,
                            'student_id' => $student->id,
                            'fee_head_id' => $structure->fee_head_id,
                            'month' => $validated['month'],
                            'year' => $validated['year'],
                        ],
                        [
                            'amount' => $structure->amount,
                            'discount' => $structure->discount,
                            'fine' => $fine,
                            'arrears' => $arrears,
                            'carried_forward_amount' => 0,
                            'due_date' => $dueDate,
                            'status' => 'unpaid',
                        ],
                    );

                    if ($fee->wasRecentlyCreated) {
                        if (! $carryForwardApplied) {
                            if (($previousBalance + $unpaidFineBalance) > 0 && ! StudentFeeLedgerEntry::forSchool($schoolId)->where('student_id', $student->id)->exists()) {
                                StudentFeeLedgerEntry::recordEntry(
                                    $schoolId,
                                    $student->id,
                                    $fee->id,
                                    'adjustment',
                                    'Previous balance brought forward',
                                    $previousBalance + $unpaidFineBalance,
                                    0,
                                    $dueDate,
                                    SchoolContext::user()->id,
                                );
                            }

                            $this->carryExistingBalancesForward($previousFees, $fee);
                            $this->linkUnpaidFinesToFee($unlinkedFines, $fee);
                            $totalPreviousBalance += $previousBalance + $unpaidFineBalance;
                            $carryForwardApplied = true;
                        }

                        $fee->updateStatusAndSave();
                        $this->recordFeeLedger($fee, $dueDate);
                        $created++;
                        $totalAmount += $fee->payableAmount();
                    } else {
                        $skipped++;
                    }
                }
            }
        });

        if ($created === 0 && $skipped > 0) {
            throw ValidationException::withMessages([
                'fee_to_generate' => 'This fee has already been generated for selected students.',
            ]);
        }

        $monthLabel = now()->setDate((int) $validated['year'], (int) $validated['month'], 1)->format('F Y');

        Activity::log('fees_generated', "Generated {$created} fee rows; skipped {$skipped} duplicates.", $validated + [
            'fee_generated_for' => $feeGeneratedFor,
            'students_processed' => $students->count(),
            'records_created' => $created,
            'duplicates_skipped' => $skipped,
            'total_amount' => $totalAmount,
            'previous_balance_carried_forward' => $totalPreviousBalance,
        ]);

        return back()->with('status', 'Fee generated for: '.$feeGeneratedFor.'. Month: '.$monthLabel.'. Students processed: '.$students->count().'. Fee records created: '.$created.'. Duplicates skipped: '.$skipped.'. Total amount generated: PKR '.number_format($totalAmount, 2).'. Previous balance carried forward: PKR '.number_format($totalPreviousBalance, 2).'.');
    }

    public function payment(Request $request, StudentFee $studentFee): RedirectResponse
    {
        abort_unless((int) $studentFee->school_id === SchoolContext::id(), 404);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'method' => ['required', Rule::in(['cash', 'bank_transfer', 'easypaisa', 'jazzcash', 'other'])],
            'paid_on' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:120'],
            'proof' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:4096'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        if ((float) $validated['amount'] > $studentFee->balance()) {
            return back()->withErrors(['amount' => 'Payment amount cannot exceed remaining balance.']);
        }

        $payment = DB::transaction(function () use ($request, $studentFee, $validated) {
            $proofPath = $request->hasFile('proof') ? $request->file('proof')->store('payment-proofs/'.SchoolContext::id(), 'public') : null;

            return $this->createPaymentForFee($studentFee, $validated, (float) $validated['amount'], $proofPath);
        });

        return redirect()->route('fees.receipt', $payment)->with('status', 'Payment recorded successfully.');
    }

    public function updateFee(Request $request, StudentFee $studentFee): RedirectResponse
    {
        abort_unless((int) $studentFee->school_id === SchoolContext::id(), 404);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'fine' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'arrears' => ['nullable', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $discount = (float) ($validated['discount'] ?? 0);
        $gross = (float) $validated['amount'] + (float) ($validated['fine'] ?? 0) + (float) ($validated['arrears'] ?? 0);

        if ($discount > $gross) {
            throw ValidationException::withMessages([
                'discount' => 'Discount cannot be greater than total fee plus fine and arrears.',
            ]);
        }

        $oldPayable = $studentFee->payableAmount();

        $studentFee->fill($validated + [
            'fine' => $validated['fine'] ?? 0,
            'discount' => $discount,
            'arrears' => $validated['arrears'] ?? 0,
        ]);
        $oldStatus = $studentFee->status;
        $studentFee->updateStatusAndSave();

        Activity::log('fee_record_updated', 'Student fee record updated.', ['student_fee_id' => $studentFee->id]);
        $payableDifference = $studentFee->payableAmount() - $oldPayable;

        if (abs($payableDifference) > 0.009) {
            StudentFeeLedgerEntry::recordEntry(
                SchoolContext::id(),
                $studentFee->student_id,
                $studentFee->id,
                'adjustment',
                'Fee account adjusted',
                $payableDifference > 0 ? $payableDifference : 0,
                $payableDifference < 0 ? abs($payableDifference) : 0,
                today()->toDateString(),
                SchoolContext::user()->id,
            );
            Activity::log('student_ledger_updated', 'Student fee ledger updated after fee adjustment.', ['student_fee_id' => $studentFee->id]);
        }

        if ($oldStatus !== $studentFee->status) {
            Activity::log('fee_status_changed', "Fee status changed from {$oldStatus} to {$studentFee->status}.", ['student_fee_id' => $studentFee->id]);
        }

        return back()->with('status', 'Fee record updated.');
    }

    public function addFine(Request $request, StudentFee $studentFee): RedirectResponse
    {
        abort_unless((int) $studentFee->school_id === SchoolContext::id(), 404);

        $validated = $request->validate([
            'amount' => ['nullable', 'numeric', 'min:0.01', 'required_without:fine'],
            'fine' => ['nullable', 'numeric', 'min:0.01', 'required_without:amount'],
            'fine_type' => ['nullable', Rule::in(array_keys(StudentFine::TYPES))],
            'title' => ['nullable', 'string', 'max:160'],
            'fine_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $amount = (float) ($validated['amount'] ?? $validated['fine']);
        $title = $validated['title'] ?? 'Fee penalty';
        $note = $validated['note'] ?? $validated['notes'] ?? null;

        DB::transaction(function () use ($amount, $note, $studentFee, $title, $validated) {
            $fine = StudentFine::create([
                'school_id' => SchoolContext::id(),
                'student_id' => $studentFee->student_id,
                'student_fee_id' => $studentFee->id,
                'fine_type' => $validated['fine_type'] ?? 'late_fee',
                'title' => $title,
                'amount' => $amount,
                'applied_amount' => $amount,
                'fine_date' => $validated['fine_date'] ?? today(),
                'due_date' => $validated['due_date'] ?? $studentFee->due_date,
                'status' => 'unpaid',
                'note' => $note,
                'created_by' => SchoolContext::user()->id,
            ]);

            $studentFee->fine = (float) $studentFee->fine + $amount;
            $studentFee->notes = trim(($studentFee->notes ? $studentFee->notes."\n" : '').($note ?: 'Fine added: '.$title));
            $studentFee->updateStatusAndSave();

            StudentFeeLedgerEntry::recordEntry(
                SchoolContext::id(),
                $studentFee->student_id,
                $studentFee->id,
                'fine',
                $title,
                $amount,
                0,
                ($validated['fine_date'] ?? today()->toDateString()),
                SchoolContext::user()->id,
            );

            Activity::log('student_fine_added', 'Student fine added from fee record.', ['student_id' => $studentFee->student_id, 'fine_id' => $fine->id]);
            Activity::log('fee_balance_updated_due_to_fine', 'Fee balance updated due to linked fine.', ['student_fee_id' => $studentFee->id, 'fine_id' => $fine->id]);
            Activity::log('student_ledger_updated', 'Student fee ledger updated after fine.', ['student_fee_id' => $studentFee->id, 'fine_id' => $fine->id]);
        });

        Activity::log('fee_fine_added', 'Fine added to student fee.', ['student_fee_id' => $studentFee->id, 'fine' => $amount]);
        Activity::log('student_balance_recalculated', 'Student balance recalculated after fee fine.', ['student_id' => $studentFee->student_id]);

        return back()->with('status', 'Fine added.');
    }

    public function addDiscount(Request $request, StudentFee $studentFee): RedirectResponse
    {
        abort_unless((int) $studentFee->school_id === SchoolContext::id(), 404);

        $validated = $this->validatedDiscount($request);

        DB::transaction(function () use ($studentFee, $validated) {
            $this->applyDiscountToFee($studentFee, $validated);
        });

        return back()->with('status', 'Discount added.');
    }

    public function receipt(Payment $payment)
    {
        abort_unless((int) $payment->school_id === SchoolContext::id(), 404);
        $this->authorizeStudentForParent($payment->student_id);

        $data = [
            'school' => SchoolContext::school(),
            'payment' => $payment->load(['student', 'studentFee.feeHead']),
            'pdf' => request()->boolean('download'),
        ];

        if ($data['pdf']) {
            return Pdf::loadView('pdf.receipt', $data)->download($payment->receipt_number.'.pdf');
        }

        return view('pdf.receipt', $data);
    }

    public function challan(StudentFee $studentFee)
    {
        abort_unless((int) $studentFee->school_id === SchoolContext::id(), 404);
        $this->authorizeStudentForParent($studentFee->student_id);

        $data = [
            'school' => SchoolContext::school(),
            'fee' => $studentFee->load(['student.schoolClass', 'student.section', 'feeHead']),
            'pdf' => request()->boolean('download'),
        ];

        if ($data['pdf']) {
            return Pdf::loadView('pdf.challan', $data)->download('fee-challan-'.$studentFee->id.'.pdf');
        }

        return view('pdf.challan', $data);
    }

    private function studentAccountData(Student $student): array
    {
        $schoolId = SchoolContext::id();
        $student->load([
            'schoolClass',
            'section',
            'fees' => fn ($query) => $query->with(['feeHead', 'payments.receiver'])->latest('year')->latest('month')->latest(),
            'fines' => fn ($query) => $query->with(['studentFee.feeHead', 'creator'])->latest('fine_date'),
            'discounts' => fn ($query) => $query->with(['studentFee.feeHead', 'creator'])->latest('discount_date'),
            'feeLedgerEntries' => fn ($query) => $query->with(['studentFee.feeHead', 'creator'])->latest('entry_date')->latest(),
        ]);

        $fees = $student->fees;
        $openFees = $fees->filter(fn (StudentFee $fee) => in_array($fee->status, ['unpaid', 'partial', 'overdue'], true) && $fee->balance() > 0)->values();
        $payments = Payment::with(['studentFee.feeHead', 'receiver'])
            ->forSchool($schoolId)
            ->where('student_id', $student->id)
            ->latest('paid_on')
            ->latest()
            ->get();
        $latestReceiptPayment = session('latest_payment_id')
            ? Payment::forSchool($schoolId)->where('student_id', $student->id)->find(session('latest_payment_id'))
            : $payments->first();

        return [
            'student' => $student,
            'fees' => $fees,
            'openFees' => $openFees,
            'payments' => $payments,
            'latestReceiptPayment' => $latestReceiptPayment,
            'summary' => [
                'totalDue' => $fees->sum(fn (StudentFee $fee) => $fee->payableAmount()),
                'totalPaid' => $fees->sum('paid_amount'),
                'remainingBalance' => $fees->sum(fn (StudentFee $fee) => $fee->balance()),
                'previousBalance' => $fees->sum('arrears'),
                'totalFines' => $fees->sum('fine') + $student->fines->where('status', 'unpaid')->whereNull('student_fee_id')->sum('amount'),
                'totalDiscounts' => $fees->sum('discount'),
            ],
            'fineTypes' => StudentFine::TYPES,
            'discountTypes' => StudentDiscount::TYPES,
            'ledgerTypes' => StudentFeeLedgerEntry::TYPES,
        ];
    }

    private function createPaymentForFee(StudentFee $studentFee, array $validated, float $amount, ?string $proofPath = null): Payment
    {
        if ($amount <= 0 || $amount > $studentFee->balance()) {
            throw ValidationException::withMessages([
                'amount' => 'Payment amount cannot exceed remaining balance.',
            ]);
        }

        $payment = Payment::create([
            'school_id' => SchoolContext::id(),
            'student_fee_id' => $studentFee->id,
            'student_id' => $studentFee->student_id,
            'receipt_number' => 'RCPT-'.now()->format('Ymd').'-'.str_pad((string) (Payment::forSchool(SchoolContext::id())->count() + 1), 5, '0', STR_PAD_LEFT),
            'amount' => $amount,
            'method' => $validated['method'],
            'reference_number' => $validated['reference_number'] ?? null,
            'proof_path' => $proofPath,
            'paid_on' => $validated['paid_on'],
            'received_by' => SchoolContext::user()->id,
            'note' => $validated['note'] ?? null,
        ]);

        $studentFee->paid_amount = (float) $studentFee->paid_amount + $amount;
        $studentFee->updateStatusAndSave();

        if ($studentFee->status === 'paid') {
            $studentFee->fines()->where('status', 'unpaid')->update(['status' => 'paid']);
        }

        StudentFeeLedgerEntry::recordEntry(
            SchoolContext::id(),
            $studentFee->student_id,
            $studentFee->id,
            'payment',
            'Payment received: '.$payment->receipt_number,
            0,
            $amount,
            $validated['paid_on'],
            SchoolContext::user()->id,
        );

        Activity::log('payment_marked', 'Payment received: '.$payment->receipt_number, ['payment_id' => $payment->id]);
        Activity::log('student_ledger_updated', 'Student fee ledger updated after payment.', ['payment_id' => $payment->id]);

        return $payment;
    }

    private function validatedDiscount(Request $request): array
    {
        return $request->validate([
            'discount' => ['required', 'numeric', 'min:0.01'],
            'discount_type' => ['nullable', Rule::in(array_keys(StudentDiscount::TYPES))],
            'reason' => ['nullable', 'string', 'max:160'],
            'approved_by' => ['nullable', 'string', 'max:160'],
            'discount_date' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function applyDiscountToFee(StudentFee $studentFee, array $validated): StudentDiscount
    {
        if ((float) $validated['discount'] > $studentFee->balance()) {
            throw ValidationException::withMessages([
                'discount' => 'Discount cannot be greater than payable amount.',
            ]);
        }

        $newDiscount = (float) $studentFee->discount + (float) $validated['discount'];
        $gross = (float) $studentFee->amount + (float) $studentFee->fine + (float) $studentFee->arrears;

        if ($newDiscount > $gross) {
            throw ValidationException::withMessages([
                'discount' => 'Discount cannot be greater than total fee plus fine and arrears.',
            ]);
        }

        $reason = $validated['reason'] ?? $validated['notes'] ?? 'Fee discount / waiver';
        $note = $validated['note'] ?? $validated['notes'] ?? null;

        $discount = StudentDiscount::create([
            'school_id' => SchoolContext::id(),
            'student_id' => $studentFee->student_id,
            'student_fee_id' => $studentFee->id,
            'discount_type' => $validated['discount_type'] ?? 'partial_fee_waiver',
            'amount' => $validated['discount'],
            'reason' => $reason,
            'approved_by' => $validated['approved_by'] ?? SchoolContext::user()->name,
            'discount_date' => $validated['discount_date'] ?? today(),
            'note' => $note,
            'created_by' => SchoolContext::user()->id,
        ]);

        $studentFee->discount = $newDiscount;
        $studentFee->notes = trim(($studentFee->notes ? $studentFee->notes."\n" : '').$reason);
        $studentFee->updateStatusAndSave();

        StudentFeeLedgerEntry::recordEntry(
            SchoolContext::id(),
            $studentFee->student_id,
            $studentFee->id,
            'discount',
            $reason,
            0,
            (float) $validated['discount'],
            ($validated['discount_date'] ?? today()->toDateString()),
            SchoolContext::user()->id,
        );

        Activity::log('fee_discount_added', 'Discount added to student fee.', ['student_fee_id' => $studentFee->id, 'discount_id' => $discount->id, 'discount' => $validated['discount']]);
        Activity::log('student_ledger_updated', 'Student fee ledger updated after discount.', ['student_fee_id' => $studentFee->id, 'discount_id' => $discount->id]);

        return $discount;
    }

    private function authorizeStudentForParent(int $studentId): void
    {
        if (SchoolContext::user()->role !== 'parent') {
            return;
        }

        $ownsStudent = Guardian::forSchool(SchoolContext::id())
            ->where('user_id', SchoolContext::user()->id)
            ->whereHas('students', fn ($query) => $query->where('students.id', $studentId))
            ->exists();

        abort_unless($ownsStudent, 404);
    }

    private function refreshOverdueFees(int $schoolId): void
    {
        StudentFee::forSchool($schoolId)
            ->whereIn('status', ['unpaid', 'partial'])
            ->whereDate('due_date', '<', today())
            ->get()
            ->each(function (StudentFee $fee) {
                $fee->updateStatusAndSave();
            });
    }

    private function carryExistingBalancesForward($previousFees, StudentFee $newFee): void
    {
        foreach ($previousFees as $previousFee) {
            $balance = $previousFee->balance();

            if ($balance <= 0) {
                continue;
            }

            $previousFee->carried_forward_amount = (float) $previousFee->carried_forward_amount + $balance;
            $previousFee->notes = trim(($previousFee->notes ? $previousFee->notes."\n" : '')."Remaining PKR ".number_format($balance, 2)." carried forward to {$newFee->month}/{$newFee->year}.");
            $previousFee->updateStatusAndSave();
        }
    }

    private function linkUnpaidFinesToFee($unlinkedFines, StudentFee $newFee): void
    {
        foreach ($unlinkedFines as $fine) {
            $fine->forceFill([
                'student_fee_id' => $newFee->id,
                'applied_amount' => $fine->amount,
            ])->save();
        }
    }

    private function recordFeeLedger(StudentFee $fee, string $entryDate): void
    {
        $description = sprintf(
            '%s %s fee',
            now()->setDate((int) $fee->year, (int) $fee->month, 1)->format('F Y'),
            $fee->feeHead?->name ?: 'School'
        );

        StudentFeeLedgerEntry::recordEntry(
            SchoolContext::id(),
            $fee->student_id,
            $fee->id,
            'fee',
            $description,
            (float) $fee->amount + (float) $fee->fine,
            0,
            $entryDate,
            SchoolContext::user()->id,
        );

        if ((float) $fee->discount > 0) {
            StudentFeeLedgerEntry::recordEntry(
                SchoolContext::id(),
                $fee->student_id,
                $fee->id,
                'discount',
                'Plan discount applied',
                0,
                (float) $fee->discount,
                $entryDate,
                SchoolContext::user()->id,
            );
        }

        Activity::log('student_ledger_updated', 'Student fee ledger updated after monthly fee generation.', ['student_fee_id' => $fee->id]);
    }

    private function structureAlreadyGenerated(FeeStructure $structure, Student $student, int $month, int $year, int $schoolId): bool
    {
        return StudentFee::forSchool($schoolId)
            ->where('student_id', $student->id)
            ->where('fee_head_id', $structure->fee_head_id)
            ->where('month', $month)
            ->where('year', $year)
            ->exists();
    }
}
