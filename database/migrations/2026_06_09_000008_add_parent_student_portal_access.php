<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('school_id')->constrained()->nullOnDelete();
            }
        });

        Schema::table('parent_student', function (Blueprint $table) {
            if (! Schema::hasColumn('parent_student', 'school_id')) {
                $table->foreignId('school_id')->nullable()->after('id')->constrained('schools')->cascadeOnDelete();
            }

            if (! Schema::hasColumn('parent_student', 'relationship_type')) {
                $table->string('relationship_type')->default('guardian')->after('student_id');
            }

            if (! Schema::hasColumn('parent_student', 'is_primary')) {
                $table->boolean('is_primary')->default(false)->after('relationship_type');
            }
        });

        if (Schema::hasColumn('parent_student', 'school_id')) {
            DB::table('parent_student')
                ->whereNull('school_id')
                ->orderBy('id')
                ->chunkById(100, function ($rows) {
                    $schoolIds = DB::table('parents')
                        ->whereIn('id', $rows->pluck('parent_id')->filter()->unique())
                        ->pluck('school_id', 'id');

                    foreach ($rows as $row) {
                        $schoolId = $schoolIds[$row->parent_id] ?? null;

                        if ($schoolId) {
                            DB::table('parent_student')
                                ->where('id', $row->id)
                                ->update(['school_id' => $schoolId]);
                        }
                    }
                });
        }

        Schema::table('notices', function (Blueprint $table) {
            if (! Schema::hasColumn('notices', 'target_student_id')) {
                $table->foreignId('target_student_id')->nullable()->after('section_id')->constrained('students')->nullOnDelete();
            }

            if (! Schema::hasColumn('notices', 'target_user_id')) {
                $table->foreignId('target_user_id')->nullable()->after('target_student_id')->constrained('users')->nullOnDelete();
            }
        });

        Schema::table('message_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('message_settings', 'allow_student_messages')) {
                $table->boolean('allow_student_messages')->default(false)->after('allow_teacher_to_parent_messages');
            }

            if (! Schema::hasColumn('message_settings', 'allow_teacher_class_notices')) {
                $table->boolean('allow_teacher_class_notices')->default(false)->after('allow_student_messages');
            }
        });
    }

    public function down(): void
    {
        Schema::table('message_settings', function (Blueprint $table) {
            if (Schema::hasColumn('message_settings', 'allow_teacher_class_notices')) {
                $table->dropColumn('allow_teacher_class_notices');
            }

            if (Schema::hasColumn('message_settings', 'allow_student_messages')) {
                $table->dropColumn('allow_student_messages');
            }
        });

        Schema::table('notices', function (Blueprint $table) {
            if (Schema::hasColumn('notices', 'target_user_id')) {
                $table->dropConstrainedForeignId('target_user_id');
            }

            if (Schema::hasColumn('notices', 'target_student_id')) {
                $table->dropConstrainedForeignId('target_student_id');
            }
        });

        Schema::table('parent_student', function (Blueprint $table) {
            if (Schema::hasColumn('parent_student', 'is_primary')) {
                $table->dropColumn('is_primary');
            }

            if (Schema::hasColumn('parent_student', 'relationship_type')) {
                $table->dropColumn('relationship_type');
            }

            if (Schema::hasColumn('parent_student', 'school_id')) {
                $table->dropConstrainedForeignId('school_id');
            }
        });

        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }
        });
    }
};
