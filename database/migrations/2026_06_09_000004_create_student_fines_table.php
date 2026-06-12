<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_fines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('student_fee_id')->nullable()->constrained('student_fees')->nullOnDelete();
            $table->string('fine_type')->default('other');
            $table->string('title');
            $table->decimal('amount', 12, 2);
            $table->decimal('applied_amount', 12, 2)->default(0);
            $table->date('fine_date');
            $table->date('due_date')->nullable();
            $table->string('status')->default('unpaid');
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['school_id', 'student_id', 'status']);
            $table->index(['school_id', 'fine_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_fines');
    }
};
