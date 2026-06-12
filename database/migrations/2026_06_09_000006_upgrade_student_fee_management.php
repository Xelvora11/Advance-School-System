<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_heads', function (Blueprint $table) {
            if (! Schema::hasColumn('fee_heads', 'category_type')) {
                $table->string('category_type')->default('monthly_fee')->after('code');
            }
        });

        Schema::table('fee_structures', function (Blueprint $table) {
            if (! Schema::hasColumn('fee_structures', 'section_id')) {
                $table->foreignId('section_id')->nullable()->after('school_class_id')->constrained('sections')->nullOnDelete();
            }
        });

        Schema::table('student_fees', function (Blueprint $table) {
            if (! Schema::hasColumn('student_fees', 'carried_forward_amount')) {
                $table->decimal('carried_forward_amount', 12, 2)->default(0)->after('paid_amount');
            }
        });

        if (! Schema::hasTable('student_discounts')) {
            Schema::create('student_discounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->foreignId('student_fee_id')->nullable()->constrained('student_fees')->nullOnDelete();
                $table->string('discount_type')->default('partial_fee_waiver');
                $table->decimal('amount', 12, 2);
                $table->string('reason');
                $table->string('approved_by')->nullable();
                $table->date('discount_date');
                $table->text('note')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['school_id', 'student_id']);
                $table->index(['school_id', 'discount_date']);
            });
        }

        if (! Schema::hasTable('student_fee_ledger_entries')) {
            Schema::create('student_fee_ledger_entries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->foreignId('student_fee_id')->nullable()->constrained('student_fees')->nullOnDelete();
                $table->string('type');
                $table->string('description');
                $table->decimal('debit', 12, 2)->default(0);
                $table->decimal('credit', 12, 2)->default(0);
                $table->decimal('balance_after', 12, 2)->default(0);
                $table->date('entry_date');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['school_id', 'student_id', 'entry_date'], 'student_fee_ledger_student_date');
                $table->index(['school_id', 'type']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('student_fee_ledger_entries');
        Schema::dropIfExists('student_discounts');

        Schema::table('student_fees', function (Blueprint $table) {
            if (Schema::hasColumn('student_fees', 'carried_forward_amount')) {
                $table->dropColumn('carried_forward_amount');
            }
        });

        Schema::table('fee_structures', function (Blueprint $table) {
            if (Schema::hasColumn('fee_structures', 'section_id')) {
                $table->dropConstrainedForeignId('section_id');
            }
        });

        Schema::table('fee_heads', function (Blueprint $table) {
            if (Schema::hasColumn('fee_heads', 'category_type')) {
                $table->dropColumn('category_type');
            }
        });
    }
};
