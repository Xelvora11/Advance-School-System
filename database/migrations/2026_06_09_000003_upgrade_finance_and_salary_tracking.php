<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_structures', function (Blueprint $table) {
            $table->decimal('discount', 12, 2)->default(0)->after('amount');
            $table->unsignedTinyInteger('due_day')->nullable()->after('discount');
            $table->date('due_date')->nullable()->after('due_day');
            $table->string('frequency')->default('monthly')->after('due_date');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->string('reference_number')->nullable()->after('method');
            $table->string('proof_path')->nullable()->after('reference_number');
        });

        Schema::table('teachers', function (Blueprint $table) {
            $table->string('salary_payment_method')->nullable()->after('basic_salary');
            $table->text('bank_account_note')->nullable()->after('salary_payment_method');
        });

        Schema::create('teacher_salary_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->unsignedTinyInteger('salary_month');
            $table->unsignedSmallInteger('salary_year');
            $table->decimal('gross_salary', 12, 2)->default(0);
            $table->decimal('deductions', 12, 2)->default(0);
            $table->text('deduction_reason')->nullable();
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('balance', 12, 2)->default(0);
            $table->string('payment_status')->default('unpaid');
            $table->string('payment_method')->nullable();
            $table->date('payment_date')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['school_id', 'teacher_id', 'salary_month', 'salary_year'], 'teacher_salary_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_salary_payments');

        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn(['salary_payment_method', 'bank_account_note']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['reference_number', 'proof_path']);
        });

        Schema::table('fee_structures', function (Blueprint $table) {
            $table->dropColumn(['discount', 'due_day', 'due_date', 'frequency']);
        });
    }
};
