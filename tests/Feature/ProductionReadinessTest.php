<?php

namespace Tests\Feature;

use App\Models\FeeHead;
use App\Models\Payment;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentFee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_landing_page_uses_release_copy_and_local_asset(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Advance School Management')
            ->assertSee('school-saas-hero.webp')
            ->assertDontSee('Focused MVP')
            ->assertDontSee('cdn.tailwindcss.com', false);
    }

    public function test_receipt_pdf_template_is_self_contained(): void
    {
        $school = School::create(['name' => 'PDF School', 'primary_color' => '#0B1F3A']);
        $student = Student::create([
            'school_id' => $school->id,
            'registration_number' => 'REG-2026-0001',
            'name' => 'PDF Student',
            'status' => 'active',
        ]);
        $feeHead = FeeHead::create([
            'school_id' => $school->id,
            'name' => 'Tuition Fee',
            'default_amount' => 1000,
        ]);
        $fee = StudentFee::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'fee_head_id' => $feeHead->id,
            'month' => 1,
            'year' => 2026,
            'amount' => 1000,
            'paid_amount' => 1000,
            'status' => 'paid',
        ]);
        $payment = Payment::create([
            'school_id' => $school->id,
            'student_fee_id' => $fee->id,
            'student_id' => $student->id,
            'receipt_number' => 'RCPT-20260101-00001',
            'amount' => 1000,
            'method' => 'cash',
            'paid_on' => '2026-01-01',
        ]);

        $this->view('pdf.receipt', [
            'school' => $school,
            'payment' => $payment->load(['student', 'studentFee.feeHead']),
            'pdf' => true,
        ])
            ->assertSee('pdf-shell')
            ->assertDontSee('cdn.tailwindcss.com', false);
    }
}
