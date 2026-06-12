<?php

namespace Tests\Feature;

use App\Models\FeeHead;
use App\Models\FeeStructure;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentDiscount;
use App\Models\StudentFee;
use App\Models\StudentFeeLedgerEntry;
use App\Models\StudentFine;
use App\Models\Teacher;
use App\Models\TeacherSalaryDeduction;
use App\Models\TeacherSalaryPayment;
use App\Models\TeacherSalaryPaymentEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FinanceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_generate_and_collect_student_fees(): void
    {
        [$school, $admin, $class, $section, $student] = $this->schoolWithStudent();

        $tuition = FeeHead::create([
            'school_id' => $school->id,
            'name' => 'Tuition',
            'default_amount' => 1000,
            'frequency' => 'monthly',
            'is_active' => true,
        ]);
        $exam = FeeHead::create([
            'school_id' => $school->id,
            'name' => 'Exam Fee',
            'default_amount' => 500,
            'frequency' => 'monthly',
            'is_active' => true,
        ]);

        FeeStructure::create([
            'school_id' => $school->id,
            'fee_head_id' => $tuition->id,
            'school_class_id' => $class->id,
            'type' => 'class',
            'amount' => 1000,
            'discount' => 100,
            'due_day' => 20,
            'frequency' => 'monthly',
            'is_active' => true,
        ]);
        FeeStructure::create([
            'school_id' => $school->id,
            'fee_head_id' => $exam->id,
            'school_class_id' => $class->id,
            'type' => 'class',
            'amount' => 500,
            'frequency' => 'monthly',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('fees.generate'), [
                'fee_to_generate' => 'all_monthly',
                'month' => now()->month,
                'year' => now()->year,
                'school_class_id' => $class->id,
                'section_id' => $section->id,
                'due_date' => now()->addDays(10)->toDateString(),
                'active_only' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseCount('student_fees', 2);

        $this->actingAs($admin)
            ->post(route('fees.generate'), [
                'fee_to_generate' => 'all_monthly',
                'month' => now()->month,
                'year' => now()->year,
                'school_class_id' => $class->id,
                'section_id' => $section->id,
                'due_date' => now()->addDays(10)->toDateString(),
                'active_only' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('fee_to_generate');

        $this->assertDatabaseCount('student_fees', 2);

        $fee = StudentFee::where('student_id', $student->id)
            ->where('fee_head_id', $tuition->id)
            ->where('month', now()->month)
            ->firstOrFail();

        $this->assertSame(900.0, $fee->payableAmount());
        $this->assertSame(900.0, $fee->balance());

        $this->actingAs($admin)
            ->post(route('fees.payments.store', $fee), [
                'amount' => 400,
                'method' => 'cash',
                'paid_on' => now()->toDateString(),
                'reference_number' => 'CASH-001',
            ])
            ->assertRedirect();

        $this->assertSame('partial', $fee->fresh()->status);
        $this->assertSame(500.0, $fee->fresh()->balance());

        $nextMonth = now()->copy()->addMonth();
        $this->actingAs($admin)
            ->post(route('fees.generate'), [
                'fee_to_generate' => 'all_monthly',
                'month' => $nextMonth->month,
                'year' => $nextMonth->year,
                'school_class_id' => $class->id,
                'section_id' => $section->id,
                'due_date' => $nextMonth->copy()->day(20)->toDateString(),
                'active_only' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', fn (string $message) => str_contains($message, 'Previous balance carried forward: PKR 1,000.00'));

        $this->assertDatabaseCount('student_fees', 4);
        $this->assertSame('carried_forward', $fee->fresh()->status);
        $this->assertSame(0.0, $fee->fresh()->balance());

        $fee = StudentFee::where('student_id', $student->id)
            ->where('fee_head_id', $tuition->id)
            ->where('month', $nextMonth->month)
            ->where('year', $nextMonth->year)
            ->firstOrFail();

        $this->assertSame(1000.0, (float) $fee->arrears);
        $this->assertSame(1900.0, $fee->payableAmount());
        $this->assertSame(1900.0, $fee->balance());

        $this->actingAs($admin)
            ->post(route('fees.fine', $fee), [
                'fine' => 100,
                'notes' => 'Late payment fine.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('student_fines', [
            'school_id' => $school->id,
            'student_id' => $student->id,
            'student_fee_id' => $fee->id,
            'amount' => 100,
            'status' => 'unpaid',
        ]);

        $this->actingAs($admin)
            ->post(route('fees.discount', $fee), [
                'discount' => 100,
                'notes' => 'Sibling discount.',
            ])
            ->assertRedirect();

        $fee = $fee->fresh();
        $this->assertSame(1900.0, $fee->balance());
        $this->assertSame(1, StudentDiscount::where('student_fee_id', $fee->id)->count());

        $this->actingAs($admin)
            ->post(route('fees.payments.store', $fee), [
                'amount' => 1900,
                'method' => 'bank_transfer',
                'paid_on' => now()->toDateString(),
            ])
            ->assertRedirect();

        $this->assertSame('paid', $fee->fresh()->status);
        $this->assertSame(0.0, $fee->fresh()->balance());
        $this->assertDatabaseHas('payments', [
            'school_id' => $school->id,
            'student_id' => $student->id,
            'reference_number' => 'CASH-001',
        ]);
        $this->assertDatabaseHas('student_fee_ledger_entries', [
            'school_id' => $school->id,
            'student_id' => $student->id,
            'type' => 'fee',
        ]);
        $this->assertDatabaseHas('student_fee_ledger_entries', [
            'school_id' => $school->id,
            'student_id' => $student->id,
            'type' => 'payment',
        ]);
        $this->assertDatabaseHas('student_fee_ledger_entries', [
            'school_id' => $school->id,
            'student_id' => $student->id,
            'type' => 'fine',
        ]);
        $this->assertDatabaseHas('student_fee_ledger_entries', [
            'school_id' => $school->id,
            'student_id' => $student->id,
            'type' => 'discount',
        ]);
        $this->assertGreaterThanOrEqual(6, StudentFeeLedgerEntry::where('student_id', $student->id)->count());

        $this->actingAs($admin)->get(route('school.dashboard'))->assertOk()->assertSee('Collected This Month');
        $this->actingAs($admin)->get(route('students.show', $student))->assertOk()->assertSee('Full Ledger History');
        $this->actingAs($admin)->get(route('fees.index'))->assertOk()->assertSee('Fees Management')->assertSee('Student Fee Accounts');
        $this->actingAs($admin)->get(route('reports.index'))->assertOk()->assertSee('Payment Collections')->assertSee('Discounts / Waivers');
        $this->actingAs($admin)->get(route('reports.payments.csv'))->assertOk()->assertSee('Receipt,Date,Student');
        $this->actingAs($admin)->get(route('reports.student-ledger.csv'))->assertOk()->assertSee('Date,Student,Registration');
        $this->actingAs($admin)->get(route('reports.student-discounts.csv'))->assertOk()->assertSee('Student,Registration,Class');
    }

    public function test_school_admin_can_generate_selected_fee_category_only(): void
    {
        [$school, $admin, $class, $section, $student] = $this->schoolWithStudent();

        $tuition = FeeHead::create([
            'school_id' => $school->id,
            'name' => 'Monthly Tuition Fee',
            'default_amount' => 2000,
            'frequency' => 'monthly',
            'is_active' => true,
        ]);
        $exam = FeeHead::create([
            'school_id' => $school->id,
            'name' => 'Exam Fee',
            'default_amount' => 750,
            'frequency' => 'monthly',
            'is_active' => true,
        ]);

        foreach ([[$tuition, 2000], [$exam, 750]] as [$feeHead, $amount]) {
            FeeStructure::create([
                'school_id' => $school->id,
                'fee_head_id' => $feeHead->id,
                'school_class_id' => $class->id,
                'type' => 'class',
                'amount' => $amount,
                'frequency' => 'monthly',
                'is_active' => true,
            ]);
        }

        $this->actingAs($admin)
            ->get(route('fees.index'))
            ->assertOk()
            ->assertSee('Fee to Generate')
            ->assertSee('Fee Month')
            ->assertSee('Fee Year')
            ->assertSee('Fee Due Date')
            ->assertSee('All Monthly Fees')
            ->assertSee('Monthly Tuition Fee')
            ->assertSee('Exam Fee');

        $this->actingAs($admin)
            ->post(route('fees.generate'), [
                'fee_to_generate' => 'fee_head:'.$tuition->id,
                'month' => now()->month,
                'year' => now()->year,
                'school_class_id' => $class->id,
                'section_id' => $section->id,
                'due_date' => now()->addDays(10)->toDateString(),
                'active_only' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', fn (string $message) => str_contains($message, 'Fee generated for: Monthly Tuition Fee'));

        $this->assertDatabaseHas('student_fees', [
            'school_id' => $school->id,
            'student_id' => $student->id,
            'fee_head_id' => $tuition->id,
            'month' => now()->month,
            'year' => now()->year,
        ]);
        $this->assertDatabaseMissing('student_fees', [
            'school_id' => $school->id,
            'student_id' => $student->id,
            'fee_head_id' => $exam->id,
            'month' => now()->month,
            'year' => now()->year,
        ]);

        $this->actingAs($admin)
            ->post(route('fees.generate'), [
                'fee_to_generate' => 'fee_head:'.$exam->id,
                'month' => now()->month,
                'year' => now()->year,
                'school_class_id' => $class->id,
                'section_id' => $section->id,
                'due_date' => now()->addDays(10)->toDateString(),
                'active_only' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', fn (string $message) => str_contains($message, 'Fee generated for: Exam Fee'));

        $this->assertDatabaseCount('student_fees', 2);

        $nextMonth = now()->copy()->addMonth();
        $this->actingAs($admin)
            ->post(route('fees.generate'), [
                'fee_to_generate' => 'all_monthly',
                'month' => $nextMonth->month,
                'year' => $nextMonth->year,
                'school_class_id' => $class->id,
                'section_id' => $section->id,
                'due_date' => $nextMonth->copy()->day(20)->toDateString(),
                'active_only' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', fn (string $message) => str_contains($message, 'Fee generated for: All Monthly Fees'));

        $this->assertSame(2, StudentFee::where('month', $nextMonth->month)->where('year', $nextMonth->year)->count());

        $this->actingAs($admin)
            ->post(route('fees.generate'), [
                'fee_to_generate' => 'all_monthly',
                'month' => $nextMonth->month,
                'year' => $nextMonth->year,
                'school_class_id' => $class->id,
                'section_id' => $section->id,
                'due_date' => $nextMonth->copy()->day(20)->toDateString(),
                'active_only' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('fee_to_generate');

        $this->assertDatabaseCount('student_fees', 4);
    }

    public function test_school_admin_can_manage_teacher_salary_records(): void
    {
        [$school, $admin] = $this->schoolAdmin();
        $teacher = Teacher::create([
            'school_id' => $school->id,
            'name' => 'Salary Teacher',
            'email' => 'salary-teacher@example.com',
            'basic_salary' => 50000,
            'salary_payment_method' => 'cash',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get(route('school.dashboard'))
            ->assertOk()
            ->assertSee('Salaries');

        $this->actingAs($admin)
            ->post(route('teachers.salary.store', $teacher), [
                'salary_month' => now()->month,
                'salary_year' => now()->year,
                'deductions' => 5000,
                'deduction_reason' => 'Advance adjustment',
            ])
            ->assertRedirect();

        $salary = TeacherSalaryPayment::where('teacher_id', $teacher->id)->firstOrFail();
        $this->assertSame(45000.0, $salary->payableAmount());
        $this->assertSame('unpaid', $salary->payment_status);

        $this->actingAs($admin)
            ->post(route('teachers.salary.store', $teacher), [
                'salary_month' => now()->month,
                'salary_year' => now()->year,
            ])
            ->assertSessionHasErrors('salary_month');

        $this->actingAs($admin)
            ->patch(route('teachers.salary.payment', $salary), [
                'amount' => 20000,
                'payment_method' => 'cash',
                'payment_date' => now()->toDateString(),
                'reference_number' => 'SAL-001',
                'note' => 'First salary payment.',
            ])
            ->assertRedirect();

        $salary = $salary->fresh();
        $this->assertSame('partial', $salary->payment_status);
        $this->assertSame(25000.0, (float) $salary->balance);
        $this->assertDatabaseHas('teacher_salary_payment_entries', [
            'school_id' => $school->id,
            'salary_record_id' => $salary->id,
            'amount' => 20000,
            'reference_number' => 'SAL-001',
        ]);

        $this->actingAs($admin)
            ->patch(route('teachers.salary.deduction', $salary), [
                'deductions' => 5000,
                'deduction_reason' => 'Leave deduction',
                'note' => 'Two unpaid leaves.',
            ])
            ->assertRedirect();

        $salary = $salary->fresh();
        $this->assertSame(40000.0, $salary->payableAmount());
        $this->assertSame(20000.0, (float) $salary->balance);
        $this->assertSame(2, TeacherSalaryDeduction::where('salary_record_id', $salary->id)->count());

        $this->actingAs($admin)
            ->patch(route('teachers.salary.payment', $salary), [
                'amount' => 20000,
                'payment_method' => 'bank_transfer',
                'payment_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $salary = $salary->fresh();
        $this->assertSame('paid', $salary->payment_status);
        $this->assertSame(0.0, (float) $salary->balance);
        $this->assertSame(2, TeacherSalaryPaymentEntry::where('salary_record_id', $salary->id)->count());

        $this->actingAs($admin)
            ->get(route('teachers.show', $teacher))
            ->assertOk()
            ->assertSee('Salary Records')
            ->assertSee('PKR 50,000.00')
            ->assertSee('Payment History')
            ->assertSee('Deduction History');

        $this->actingAs($admin)
            ->get(route('salaries.index', ['teacher_id' => $teacher->id]))
            ->assertOk()
            ->assertSee('Salary Teacher')
            ->assertSee('Salary Management');

        $this->actingAs($admin)
            ->get(route('salaries.show', $salary))
            ->assertOk()
            ->assertSee('Payment Entries')
            ->assertSee('Deduction Entries');

        $teacherUser = User::create([
            'school_id' => $school->id,
            'name' => 'Teacher Login',
            'email' => 'teacher-salary-page@example.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_TEACHER,
            'is_active' => true,
        ]);

        $this->actingAs($teacherUser)
            ->get(route('salaries.index'))
            ->assertForbidden();

        $parentUser = User::create([
            'school_id' => $school->id,
            'name' => 'Parent Login',
            'email' => 'parent-salary-page@example.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_PARENT,
            'is_active' => true,
        ]);

        $this->actingAs($parentUser)
            ->get(route('salaries.index'))
            ->assertForbidden();
    }

    public function test_school_admin_can_open_student_fee_account_and_record_manual_payment(): void
    {
        [$school, $admin, $class, $section, $student] = $this->schoolWithStudent();
        $student->update([
            'guardian_name' => 'Fee Guardian',
            'guardian_phone' => '03001234567',
        ]);

        $feeHead = FeeHead::create([
            'school_id' => $school->id,
            'name' => 'Monthly Tuition Fee',
            'category_type' => 'monthly_fee',
            'default_amount' => 2000,
            'frequency' => 'monthly',
            'is_active' => true,
        ]);

        $fee = StudentFee::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'fee_head_id' => $feeHead->id,
            'month' => now()->month,
            'year' => now()->year,
            'amount' => 2000,
            'due_date' => now()->addWeek()->toDateString(),
            'status' => 'unpaid',
        ]);

        StudentFeeLedgerEntry::recordEntry(
            $school->id,
            $student->id,
            $fee->id,
            'fee',
            'Monthly Tuition Fee',
            2000,
            0,
            now()->toDateString(),
            $admin->id,
        );

        $this->actingAs($admin)
            ->get(route('fees.index', ['tab' => 'accounts', 'search' => '03001234567']))
            ->assertOk()
            ->assertSee('Student Fee Accounts')
            ->assertSee('03001234567')
            ->assertSee('View Full Fee Account');

        $this->actingAs($admin)
            ->get(route('fees.students.account', $student))
            ->assertOk()
            ->assertSee('Student Fee Account')
            ->assertSee('Receive Manual Payment')
            ->assertSee('Remaining Balance')
            ->assertSee('PKR 2,000.00');

        $this->actingAs($admin)
            ->post(route('fees.students.payments.store', $student), [
                'amount' => 1000,
                'method' => 'cash',
                'paid_on' => now()->toDateString(),
                'reference_number' => 'OFFICE-001',
                'note' => 'Manual office payment.',
            ])
            ->assertRedirect(route('fees.students.account', $student))
            ->assertSessionHas('status', 'Payment recorded successfully.');

        $fee = $fee->fresh();
        $this->assertSame('partial', $fee->status);
        $this->assertSame(1000.0, (float) $fee->paid_amount);
        $this->assertSame(1000.0, $fee->balance());
        $this->assertDatabaseHas('payments', [
            'school_id' => $school->id,
            'student_fee_id' => $fee->id,
            'student_id' => $student->id,
            'amount' => 1000,
            'reference_number' => 'OFFICE-001',
        ]);
        $this->assertDatabaseHas('student_fee_ledger_entries', [
            'school_id' => $school->id,
            'student_id' => $student->id,
            'student_fee_id' => $fee->id,
            'type' => 'payment',
            'credit' => 1000,
        ]);

        $this->actingAs($admin)
            ->post(route('fees.students.discounts.store', $student), [
                'student_fee_id' => $fee->id,
                'discount' => 250,
                'discount_type' => 'scholarship',
                'reason' => 'Merit scholarship',
                'approved_by' => 'Finance Admin',
                'discount_date' => now()->toDateString(),
            ])
            ->assertRedirect(route('fees.students.account', $student))
            ->assertSessionHas('status', 'Discount added.');

        $fee = $fee->fresh();
        $this->assertSame(250.0, (float) $fee->discount);
        $this->assertSame(750.0, $fee->balance());
        $this->assertDatabaseHas('student_discounts', [
            'school_id' => $school->id,
            'student_fee_id' => $fee->id,
            'student_id' => $student->id,
            'amount' => 250,
            'discount_type' => 'scholarship',
            'reason' => 'Merit scholarship',
        ]);
        $this->assertDatabaseHas('student_fee_ledger_entries', [
            'school_id' => $school->id,
            'student_id' => $student->id,
            'student_fee_id' => $fee->id,
            'type' => 'discount',
            'credit' => 250,
        ]);

        $this->actingAs($admin)
            ->post(route('fees.students.payments.store', $student), [
                'amount' => 1500,
                'method' => 'cash',
                'paid_on' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('amount');

        $this->actingAs($admin)
            ->get(route('students.show', $student))
            ->assertOk()
            ->assertSee('Receive Payment')
            ->assertSee('Add Discount')
            ->assertSee('View Full Fee Account')
            ->assertSee('Download Challan')
            ->assertSee('Download Receipt')
            ->assertSee('View Ledger')
            ->assertSee('View Receipt')
            ->assertSee('PKR 750.00');

        $this->actingAs($admin)
            ->post(route('fees.students.payments.store', $student), [
                'amount' => 750,
                'method' => 'bank_transfer',
                'paid_on' => now()->toDateString(),
            ])
            ->assertRedirect(route('fees.students.account', $student));

        $this->assertSame('paid', $fee->fresh()->status);
        $this->assertSame(0.0, $fee->fresh()->balance());
    }

    public function test_school_admin_can_manage_student_fines_and_link_them_to_fees(): void
    {
        [$school, $admin, $class, $section, $student] = $this->schoolWithStudent();
        $feeHead = FeeHead::create([
            'school_id' => $school->id,
            'name' => 'Tuition',
            'default_amount' => 1000,
            'frequency' => 'monthly',
            'is_active' => true,
        ]);
        $fee = StudentFee::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'fee_head_id' => $feeHead->id,
            'month' => now()->month,
            'year' => now()->year,
            'amount' => 1000,
            'due_date' => now()->addWeek(),
            'status' => 'unpaid',
        ]);

        $this->actingAs($admin)
            ->post(route('students.fines.store', $student), [
                'student_fee_id' => $fee->id,
                'fine_type' => 'damage',
                'title' => 'Desk damage fine',
                'amount' => 250,
                'fine_date' => now()->toDateString(),
                'due_date' => now()->addWeek()->toDateString(),
                'note' => 'Manual fine from profile.',
            ])
            ->assertRedirect();

        $fine = StudentFine::where('student_id', $student->id)->firstOrFail();
        $this->assertSame('unpaid', $fine->status);
        $this->assertSame(250.0, (float) $fine->applied_amount);
        $this->assertSame(250.0, (float) $fee->fresh()->fine);
        $this->assertSame(1250.0, $fee->fresh()->balance());

        $this->actingAs($admin)
            ->get(route('students.show', $student))
            ->assertOk()
            ->assertSee('Fine Records')
            ->assertSee('Desk damage fine')
            ->assertSee('Unpaid Fines')
            ->assertSee('View Details')
            ->assertSee('Mark Paid')
            ->assertSee('Waive Fine')
            ->assertSee('Edit Fine')
            ->assertSee('Delete Fine')
            ->assertSee('Are you sure you want to waive this fine?');

        $this->actingAs($admin)
            ->patch(route('student-fines.paid', $fine))
            ->assertRedirect();

        $fine = $fine->fresh();
        $this->assertSame('paid', $fine->status);
        $this->assertSame(0.0, (float) $fine->applied_amount);
        $this->assertSame(0.0, (float) $fee->fresh()->fine);
        $this->assertSame(1000.0, $fee->fresh()->balance());

        $this->actingAs($admin)
            ->post(route('student-fines.store'), [
                'student_id' => $student->id,
                'fine_type' => 'lost_book',
                'title' => 'Lost library book',
                'amount' => 300,
                'fine_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $unlinkedFine = StudentFine::where('title', 'Lost library book')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('school.dashboard'))
            ->assertOk()
            ->assertSee('Unpaid Fines');

        $this->actingAs($admin)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Student Fines')
            ->assertSee('Teacher Salaries');

        $this->actingAs($admin)
            ->get(route('reports.student-fines.csv'))
            ->assertOk()
            ->assertSee('Student,Registration,Class');

        $this->actingAs($admin)
            ->patch(route('student-fines.waive', $unlinkedFine))
            ->assertRedirect();

        $this->assertSame('waived', $unlinkedFine->fresh()->status);
    }

    private function schoolWithStudent(): array
    {
        [$school, $admin] = $this->schoolAdmin();
        $class = SchoolClass::create(['school_id' => $school->id, 'name' => 'Class 1']);
        $section = Section::create(['school_id' => $school->id, 'school_class_id' => $class->id, 'name' => 'A']);
        $student = Student::create([
            'school_id' => $school->id,
            'school_class_id' => $class->id,
            'section_id' => $section->id,
            'registration_number' => 'REG-FIN-001',
            'name' => 'Finance Student',
            'status' => 'active',
        ]);

        return [$school, $admin, $class, $section, $student];
    }

    private function schoolAdmin(): array
    {
        $school = School::create([
            'name' => 'Finance School',
            'setup_completed' => true,
        ]);

        $admin = User::create([
            'school_id' => $school->id,
            'name' => 'Finance Admin',
            'email' => 'finance-admin-'.uniqid().'@example.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_SCHOOL_ADMIN,
            'is_active' => true,
        ]);

        return [$school, $admin];
    }
}
