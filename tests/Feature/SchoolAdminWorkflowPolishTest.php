<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SchoolAdminWorkflowPolishTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_dashboard_uses_admin_actions_and_attention_panel(): void
    {
        [$school, $admin] = $this->schoolAdmin();

        $this->actingAs($admin)
            ->get(route('school.dashboard'))
            ->assertOk()
            ->assertSee('Admission Inquiry')
            ->assertSee('Generate Fees')
            ->assertSee('Create Notice')
            ->assertSee('Needs Attention Today')
            ->assertSee('No attendance marked today')
            ->assertDontSee('Mark Attendance');
    }

    public function test_school_setup_preview_pages_render(): void
    {
        [$school, $admin] = $this->schoolAdmin();

        foreach (['marksheet', 'challan', 'receipt'] as $type) {
            $this->actingAs($admin)
                ->get(route('school.setup.preview', $type))
                ->assertOk()
                ->assertSee($school->name)
                ->assertSee('Template Preview');
        }
    }

    public function test_school_admin_can_assign_subject_to_class_with_optional_teacher(): void
    {
        [$school, $admin] = $this->schoolAdmin();
        $class = SchoolClass::create(['school_id' => $school->id, 'name' => 'Class 1']);
        $section = Section::create(['school_id' => $school->id, 'school_class_id' => $class->id, 'name' => 'A']);
        $subject = Subject::create(['school_id' => $school->id, 'name' => 'English']);
        $teacher = Teacher::create(['school_id' => $school->id, 'name' => 'Teacher One', 'status' => 'active']);

        $this->actingAs($admin)
            ->post(route('academics.class-subjects.store'), [
                'school_class_id' => $class->id,
                'section_id' => $section->id,
                'subject_id' => $subject->id,
                'teacher_id' => $teacher->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('class_subjects', [
            'school_id' => $school->id,
            'school_class_id' => $class->id,
            'section_id' => $section->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
        ]);
    }

    public function test_students_actions_dropdown_uses_viewport_safe_menu(): void
    {
        [$school, $admin] = $this->schoolAdmin();
        $class = SchoolClass::create(['school_id' => $school->id, 'name' => 'Class 1']);
        $section = Section::create(['school_id' => $school->id, 'school_class_id' => $class->id, 'name' => 'A']);

        Student::create([
            'school_id' => $school->id,
            'school_class_id' => $class->id,
            'section_id' => $section->id,
            'registration_number' => 'REG-ACTION-001',
            'name' => 'Action Menu Student',
            'guardian_phone' => '03000000001',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get(route('students.index'))
            ->assertOk()
            ->assertSee('Action Menu Student')
            ->assertSee('position: fixed', false)
            ->assertSee('z-[9999]', false)
            ->assertSee('View Profile')
            ->assertSee('Fees')
            ->assertSee('Change Status');
    }

    public function test_school_admin_can_store_teacher_salary_and_reset_teacher_password(): void
    {
        [$school, $admin] = $this->schoolAdmin();
        $teacherUser = User::create([
            'school_id' => $school->id,
            'name' => 'Teacher Login',
            'email' => 'teacher@polish.test',
            'password' => Hash::make('old-password'),
            'role' => User::ROLE_TEACHER,
            'is_active' => true,
        ]);
        $teacher = Teacher::create([
            'school_id' => $school->id,
            'user_id' => $teacherUser->id,
            'name' => 'Teacher Login',
            'email' => 'teacher@polish.test',
            'basic_salary' => 45000,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get(route('teachers.show', $teacher))
            ->assertOk()
            ->assertSee('PKR 45,000.00');

        $this->actingAs($admin)
            ->patch(route('teachers.password', $teacher), [
                'password' => 'new-password',
            ])
            ->assertRedirect();

        $this->assertTrue(Hash::check('new-password', $teacherUser->fresh()->password));
    }

    public function test_exam_cannot_be_published_without_subjects(): void
    {
        [$school, $admin] = $this->schoolAdmin();
        $class = SchoolClass::create(['school_id' => $school->id, 'name' => 'Class 2']);
        $exam = Exam::create([
            'school_id' => $school->id,
            'school_class_id' => $class->id,
            'name' => 'Term Exam',
            'type' => 'term_exam',
        ]);

        $this->actingAs($admin)
            ->patch(route('exams.publish', $exam))
            ->assertSessionHasErrors('exam');

        $this->assertFalse($exam->fresh()->is_published);
    }

    private function schoolAdmin(): array
    {
        $school = School::create([
            'name' => 'Polish School',
            'setup_completed' => true,
        ]);

        $admin = User::create([
            'school_id' => $school->id,
            'name' => 'School Admin',
            'email' => 'admin@polish.test',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_SCHOOL_ADMIN,
            'is_active' => true,
        ]);

        return [$school, $admin];
    }
}
