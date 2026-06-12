<?php

namespace Database\Seeders;

use App\Models\Admission;
use App\Models\Exam;
use App\Models\ExamSubject;
use App\Models\FeeHead;
use App\Models\FeeStructure;
use App\Models\Notice;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolTemplateSetting;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSchoolSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::updateOrCreate([
            'email' => 'office@demo-school.test',
        ], [
            'name' => 'Iqra Model School',
            'short_name' => 'IMS',
            'phone' => '0300-1234567',
            'address' => 'Main Road, Lahore',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'status' => 'active',
            'account_status' => 'active',
            'academic_year' => '2026-2027',
            'principal_name' => 'Mrs. Ayesha Khan',
            'default_fee_due_day' => 10,
            'setup_completed' => true,
            'plan_name' => 'Trial',
            'manual_payment_status' => 'trial',
        ]);

        $admin = User::updateOrCreate([
            'email' => 'admin@demo-school.test',
        ], [
            'school_id' => $school->id,
            'name' => 'School Admin',
            'phone' => '0300-1112223',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_SCHOOL_ADMIN,
            'is_active' => true,
        ]);

        $teacherUser = User::updateOrCreate([
            'email' => 'teacher@demo-school.test',
        ], [
            'school_id' => $school->id,
            'name' => 'Sara Teacher',
            'phone' => '0300-4445556',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_TEACHER,
            'is_active' => true,
        ]);

        $classOne = SchoolClass::updateOrCreate(['school_id' => $school->id, 'name' => 'Class 1'], ['code' => 'C1', 'sort_order' => 1, 'is_active' => true]);
        $classTwo = SchoolClass::updateOrCreate(['school_id' => $school->id, 'name' => 'Class 2'], ['code' => 'C2', 'sort_order' => 2, 'is_active' => true]);

        $sectionA = Section::updateOrCreate(['school_id' => $school->id, 'school_class_id' => $classOne->id, 'name' => 'A'], ['is_active' => true]);
        $sectionB = Section::updateOrCreate(['school_id' => $school->id, 'school_class_id' => $classTwo->id, 'name' => 'B'], ['is_active' => true]);

        $english = Subject::updateOrCreate(['school_id' => $school->id, 'name' => 'English'], ['code' => 'ENG', 'is_active' => true]);
        $math = Subject::updateOrCreate(['school_id' => $school->id, 'name' => 'Mathematics'], ['code' => 'MATH', 'is_active' => true]);
        $urdu = Subject::updateOrCreate(['school_id' => $school->id, 'name' => 'Urdu'], ['code' => 'URD', 'is_active' => true]);

        $teacher = Teacher::updateOrCreate([
            'school_id' => $school->id,
            'email' => 'teacher@demo-school.test',
        ], [
            'user_id' => $teacherUser->id,
            'name' => 'Sara Teacher',
            'phone' => '0300-4445556',
            'qualification' => 'MA English',
            'joining_date' => now()->subYear()->toDateString(),
            'status' => 'active',
        ]);

        foreach ([$english, $math] as $subject) {
            TeacherAssignment::updateOrCreate([
                'teacher_id' => $teacher->id,
                'school_class_id' => $classOne->id,
                'section_id' => $sectionA->id,
                'subject_id' => $subject->id,
            ], ['school_id' => $school->id]);
        }

        foreach ([
            ['REG-2026-0001', 'Ali Raza', '1', 'Mr. Raza', '0301-1111111'],
            ['REG-2026-0002', 'Fatima Noor', '2', 'Mr. Noor', '0302-2222222'],
            ['REG-2026-0003', 'Hassan Ahmed', '3', 'Mr. Ahmed', '0303-3333333'],
        ] as [$reg, $name, $roll, $guardian, $phone]) {
            Student::updateOrCreate([
                'school_id' => $school->id,
                'registration_number' => $reg,
            ], [
                'school_class_id' => $classOne->id,
                'section_id' => $sectionA->id,
                'name' => $name,
                'roll_number' => $roll,
                'admission_date' => now()->subMonths(8)->toDateString(),
                'gender' => str_contains($name, 'Fatima') ? 'female' : 'male',
                'father_name' => $guardian,
                'guardian_name' => $guardian,
                'guardian_phone' => $phone,
                'status' => 'active',
            ]);
        }

        $tuition = FeeHead::updateOrCreate(['school_id' => $school->id, 'name' => 'Tuition Fee'], ['code' => 'TUI', 'default_amount' => 3500, 'frequency' => 'monthly', 'is_active' => true]);
        $examFee = FeeHead::updateOrCreate(['school_id' => $school->id, 'name' => 'Exam Fee'], ['code' => 'EXM', 'default_amount' => 800, 'frequency' => 'once', 'is_active' => true]);

        foreach ([$tuition, $examFee] as $head) {
            FeeStructure::updateOrCreate([
                'school_id' => $school->id,
                'fee_head_id' => $head->id,
                'school_class_id' => $classOne->id,
                'type' => 'class',
            ], ['amount' => $head->default_amount, 'is_active' => true]);
        }

        $exam = Exam::updateOrCreate([
            'school_id' => $school->id,
            'school_class_id' => $classOne->id,
            'section_id' => $sectionA->id,
            'name' => 'First Term Exam',
        ], [
            'type' => 'term_exam',
            'exam_date' => now()->addWeeks(2)->toDateString(),
            'session' => '2026-2027',
            'is_published' => false,
        ]);

        foreach ([$english, $math, $urdu] as $subject) {
            ExamSubject::updateOrCreate([
                'school_id' => $school->id,
                'exam_id' => $exam->id,
                'subject_id' => $subject->id,
            ], ['total_marks' => 100, 'passing_marks' => 33]);
        }

        Notice::updateOrCreate([
            'school_id' => $school->id,
            'title' => 'Fee submission reminder',
        ], [
            'body' => 'Monthly fee due date is the 10th of this month.',
            'audience_type' => 'all_parents',
            'is_published' => true,
            'published_at' => now(),
            'created_by' => $admin->id,
        ]);

        Admission::updateOrCreate([
            'school_id' => $school->id,
            'student_name' => 'Zainab Khan',
        ], [
            'requested_class_id' => $classTwo->id,
            'gender' => 'female',
            'guardian_name' => 'Mr. Khan',
            'guardian_phone' => '0304-4444444',
            'address' => 'Lahore',
            'status' => 'pending',
            'test_notes' => 'Interview completed. Documents pending.',
            'document_checklist' => [
                'b_form' => true,
                'photos' => false,
                'previous_result' => true,
                'guardian_cnic' => false,
            ],
        ]);

        SchoolTemplateSetting::updateOrCreate([
            'school_id' => $school->id,
            'template_type' => 'marksheet',
        ], [
            'header_text' => 'Official Result Card',
            'footer_text' => 'This is a system generated result card.',
            'show_grading_table' => true,
            'show_attendance_summary' => true,
            'show_teacher_remarks' => true,
            'show_principal_remarks' => true,
        ]);
    }
}
