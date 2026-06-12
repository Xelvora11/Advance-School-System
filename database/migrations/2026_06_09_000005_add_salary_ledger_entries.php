<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->date('salary_effective_from')->nullable()->after('bank_account_note');
        });

        Schema::create('teacher_salary_payment_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('salary_record_id')->constrained('teacher_salary_payments')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('payment_method');
            $table->date('payment_date');
            $table->string('reference_number')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['school_id', 'teacher_id', 'payment_date']);
        });

        Schema::create('teacher_salary_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('salary_record_id')->constrained('teacher_salary_payments')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('reason');
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['school_id', 'teacher_id']);
        });

        DB::table('teacher_salary_payments')
            ->where('paid_amount', '>', 0)
            ->orderBy('id')
            ->get()
            ->each(function ($record): void {
                DB::table('teacher_salary_payment_entries')->insert([
                    'school_id' => $record->school_id,
                    'salary_record_id' => $record->id,
                    'teacher_id' => $record->teacher_id,
                    'amount' => $record->paid_amount,
                    'payment_method' => $record->payment_method ?: 'cash',
                    'payment_date' => $record->payment_date ?: now()->toDateString(),
                    'reference_number' => null,
                    'note' => 'Backfilled from existing salary paid amount.',
                    'created_by' => $record->created_by,
                    'created_at' => $record->created_at,
                    'updated_at' => $record->updated_at,
                ]);
            });

        DB::table('teacher_salary_payments')
            ->where('deductions', '>', 0)
            ->orderBy('id')
            ->get()
            ->each(function ($record): void {
                DB::table('teacher_salary_deductions')->insert([
                    'school_id' => $record->school_id,
                    'salary_record_id' => $record->id,
                    'teacher_id' => $record->teacher_id,
                    'amount' => $record->deductions,
                    'reason' => $record->deduction_reason ?: 'Existing deduction',
                    'note' => 'Backfilled from existing salary deduction amount.',
                    'created_by' => $record->created_by,
                    'created_at' => $record->created_at,
                    'updated_at' => $record->updated_at,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_salary_deductions');
        Schema::dropIfExists('teacher_salary_payment_entries');

        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn('salary_effective_from');
        });
    }
};
