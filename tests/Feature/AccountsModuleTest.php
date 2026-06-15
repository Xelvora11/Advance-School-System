<?php

namespace Tests\Feature;

use App\Models\AccountTransaction;
use App\Models\FeeHead;
use App\Models\Payment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentFee;
use App\Models\Teacher;
use App\Models\TeacherSalaryPayment;
use App\Models\TeacherSalaryPaymentEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountsModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_open_accounts_and_teacher_cannot(): void
    {
        [$school, $admin] = $this->schoolAdmin();
        $teacherUser = User::create([
            'school_id' => $school->id,
            'name' => 'Teacher User',
            'email' => 'teacher-accounts-'.uniqid().'@example.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_TEACHER,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('accounts.index'))
            ->assertOk()
            ->assertSee('Accounts Dashboard')
            ->assertSee('Recent Transactions');

        foreach ([
            'accounts.income',
            'accounts.expenses',
            'accounts.ledger',
            'accounts.cash-book',
            'accounts.reports.student-dues',
            'accounts.reports.salary',
            'accounts.reports.summary',
        ] as $routeName) {
            $this->actingAs($admin)
                ->get(route($routeName))
                ->assertOk();
        }

        $this->actingAs($teacherUser)
            ->get(route('accounts.index'))
            ->assertForbidden();
    }

    public function test_manual_income_and_expense_update_the_master_ledger_balance(): void
    {
        [$school, $admin] = $this->schoolAdmin();

        $this->actingAs($admin)
            ->post(route('accounts.income.store'), [
                'transaction_date' => '2026-06-10',
                'category' => 'Donation',
                'description' => 'Community donation',
                'amount' => 5000,
            ])
            ->assertRedirect(route('accounts.income'));

        $this->actingAs($admin)
            ->post(route('accounts.expenses.store'), [
                'transaction_date' => '2026-06-11',
                'category' => 'Electricity',
                'description' => 'Electricity bill',
                'amount' => 1500,
            ])
            ->assertRedirect(route('accounts.expenses'));

        $this->assertDatabaseCount('account_transactions', 2);
        $this->assertSame(3500.0, AccountTransaction::currentBalance($school->id));

        $this->actingAs($admin)
            ->get(route('accounts.ledger'))
            ->assertOk()
            ->assertSee('Community donation')
            ->assertSee('Electricity bill')
            ->assertSee('PKR 3,500.00');
    }

    public function test_fee_payment_creates_income_transaction(): void
    {
        [$school, $admin, $student] = $this->schoolWithStudent();
        $feeHead = FeeHead::create([
            'school_id' => $school->id,
            'name' => 'Monthly Tuition',
            'default_amount' => 1000,
            'frequency' => 'monthly',
            'is_active' => true,
        ]);
        $fee = StudentFee::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'fee_head_id' => $feeHead->id,
            'month' => 6,
            'year' => 2026,
            'amount' => 1000,
            'due_date' => '2026-06-20',
            'status' => 'unpaid',
        ]);

        $this->actingAs($admin)
            ->post(route('fees.payments.store', $fee), [
                'amount' => 600,
                'method' => 'cash',
                'paid_on' => '2026-06-12',
            ])
            ->assertRedirect();

        $payment = Payment::where('student_fee_id', $fee->id)->firstOrFail();
        $transaction = AccountTransaction::where('reference_type', Payment::class)
            ->where('reference_id', $payment->id)
            ->firstOrFail();

        $this->assertSame('income', $transaction->type);
        $this->assertSame('Tuition Fee', $transaction->category);
        $this->assertSame('Fee Payment', $transaction->source);
        $this->assertSame(600.0, (float) $transaction->income_amount);
        $this->assertSame(600.0, AccountTransaction::currentBalance($school->id));
    }

    public function test_salary_payment_creates_expense_transaction(): void
    {
        [$school, $admin] = $this->schoolAdmin();
        $teacher = Teacher::create([
            'school_id' => $school->id,
            'name' => 'Salary Teacher',
            'basic_salary' => 4000,
            'status' => 'active',
        ]);
        $salary = TeacherSalaryPayment::create([
            'school_id' => $school->id,
            'teacher_id' => $teacher->id,
            'salary_month' => 6,
            'salary_year' => 2026,
            'gross_salary' => 4000,
            'deductions' => 0,
            'paid_amount' => 0,
            'balance' => 4000,
            'payment_status' => 'unpaid',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->patch(route('salaries.payment', $salary), [
                'amount' => 2500,
                'payment_method' => 'cash',
                'payment_date' => '2026-06-13',
            ])
            ->assertRedirect();

        $entry = TeacherSalaryPaymentEntry::where('salary_record_id', $salary->id)->firstOrFail();
        $transaction = AccountTransaction::where('reference_type', TeacherSalaryPaymentEntry::class)
            ->where('reference_id', $entry->id)
            ->firstOrFail();

        $this->assertSame('expense', $transaction->type);
        $this->assertSame('Salaries', $transaction->category);
        $this->assertSame('Salary Payment', $transaction->source);
        $this->assertSame(2500.0, (float) $transaction->expense_amount);
        $this->assertSame(-2500.0, AccountTransaction::currentBalance($school->id));
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
            'registration_number' => 'REG-ACC-001',
            'name' => 'Accounts Student',
            'status' => 'active',
        ]);

        return [$school, $admin, $student];
    }

    private function schoolAdmin(): array
    {
        $school = School::create([
            'name' => 'Accounts School',
            'setup_completed' => true,
        ]);

        $admin = User::create([
            'school_id' => $school->id,
            'name' => 'Accounts Admin',
            'email' => 'accounts-admin-'.uniqid().'@example.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_SCHOOL_ADMIN,
            'is_active' => true,
        ]);

        return [$school, $admin];
    }
}
