<?php

namespace Tests\Feature;

use App\Models\Guardian;
use App\Models\MessageSetting;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ParentStudentPortalAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_creates_parent_login_and_parent_only_sees_linked_student(): void
    {
        [$school, $admin, $class, $section] = $this->schoolSetup();
        $student = $this->student($school, $class, $section, [
            'name' => 'Linked Student',
            'guardian_name' => 'Linked Parent',
            'guardian_email' => 'linked-parent@example.com',
            'guardian_phone' => '0300-0000001',
        ]);
        $otherStudent = $this->student($school, $class, $section, [
            'name' => 'Other Student',
            'registration_number' => 'REG-OTHER',
        ]);

        $this->actingAs($admin)->post(route('students.parent-login', $student), [
            'relationship_type' => 'father',
            'password' => 'parent-pass-123',
        ])->assertRedirect();

        $parent = Guardian::with('user', 'students')->where('email', 'linked-parent@example.com')->firstOrFail();

        $this->assertSame(User::ROLE_PARENT, $parent->user->role);
        $this->assertTrue($parent->students->contains($student));
        $this->assertDatabaseHas('parent_student', [
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'school_id' => $school->id,
            'relationship_type' => 'father',
        ]);

        $this->actingAs($parent->user)
            ->get(route('parent.dashboard'))
            ->assertOk()
            ->assertSee('Linked Student')
            ->assertDontSee('Other Student');

        $this->actingAs($parent->user)
            ->get(route('parent.students.show', $student))
            ->assertOk()
            ->assertSee('Linked Student');

        $this->actingAs($parent->user)
            ->get(route('parent.students.show', $otherStudent))
            ->assertNotFound();
    }

    public function test_school_admin_creates_student_login_and_student_uses_read_only_portal(): void
    {
        [$school, $admin, $class, $section] = $this->schoolSetup('student-login');
        $student = $this->student($school, $class, $section, ['name' => 'Portal Student']);

        $this->actingAs($admin)->post(route('students.student-login', $student), [
            'email' => 'portal-student@example.com',
            'password' => 'student-pass-123',
        ])->assertRedirect();

        $student->refresh();
        $this->assertSame(User::ROLE_STUDENT, $student->user->role);
        $this->assertTrue($student->user->is_active);

        $this->actingAs($student->user)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('Portal Student');

        $this->actingAs($student->user)->get(route('student.profile'))->assertOk();
        $this->actingAs($student->user)->get(route('student.attendance'))->assertOk();
        $this->actingAs($student->user)->get(route('student.fees'))->assertOk();
        $this->actingAs($student->user)->get(route('student.results'))->assertOk();
        $this->actingAs($student->user)->get(route('student.notices'))->assertOk();

        $this->actingAs($student->user)->get(route('students.index'))->assertForbidden();
    }

    public function test_student_messages_follow_school_setting_and_teacher_assignment(): void
    {
        [$school, , $class, $section] = $this->schoolSetup('student-messages');
        $studentUser = User::create([
            'school_id' => $school->id,
            'name' => 'Message Student',
            'email' => 'message-student@example.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_STUDENT,
            'is_active' => true,
        ]);
        $student = $this->student($school, $class, $section, [
            'name' => 'Message Student',
            'registration_number' => 'REG-MSG',
            'user_id' => $studentUser->id,
        ]);
        $teacherUser = User::create([
            'school_id' => $school->id,
            'name' => 'Assigned Teacher',
            'email' => 'assigned-teacher@example.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_TEACHER,
            'is_active' => true,
        ]);
        $teacher = Teacher::create([
            'school_id' => $school->id,
            'user_id' => $teacherUser->id,
            'name' => 'Assigned Teacher',
            'email' => 'assigned-teacher@example.com',
            'status' => 'active',
        ]);
        TeacherAssignment::create([
            'school_id' => $school->id,
            'teacher_id' => $teacher->id,
            'school_class_id' => $class->id,
            'section_id' => $section->id,
        ]);

        MessageSetting::forSchoolId($school->id);

        $this->actingAs($studentUser)->post(route('messages.store'), [
            'recipient_id' => $teacherUser->id,
            'subject' => 'Question',
            'body' => 'Default settings should block student messages.',
        ])->assertSessionHasErrors('recipient_id');

        MessageSetting::forSchoolId($school->id)->update(['allow_student_messages' => true]);

        $this->actingAs($studentUser)->post(route('messages.store'), [
            'recipient_id' => $teacherUser->id,
            'subject' => 'Homework question',
            'body' => 'Please clarify the homework.',
        ])->assertRedirect();

        $this->assertDatabaseHas('messages', [
            'school_id' => $school->id,
            'sender_id' => $studentUser->id,
            'body' => 'Please clarify the homework.',
        ]);
        $this->assertSame($studentUser->id, $student->user_id);
    }

    private function schoolSetup(string $suffix = 'main'): array
    {
        $school = School::create([
            'name' => 'Portal School '.$suffix,
            'setup_completed' => true,
            'status' => 'active',
            'account_status' => 'active',
        ]);
        $class = SchoolClass::create(['school_id' => $school->id, 'name' => 'Class 1']);
        $section = Section::create(['school_id' => $school->id, 'school_class_id' => $class->id, 'name' => 'A']);
        $admin = User::create([
            'school_id' => $school->id,
            'name' => 'School Admin '.$suffix,
            'email' => 'portal-admin-'.$suffix.'@example.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_SCHOOL_ADMIN,
            'is_active' => true,
        ]);

        return [$school, $admin, $class, $section];
    }

    private function student(School $school, SchoolClass $class, Section $section, array $overrides = []): Student
    {
        return Student::create($overrides + [
            'school_id' => $school->id,
            'school_class_id' => $class->id,
            'section_id' => $section->id,
            'registration_number' => 'REG-'.uniqid(),
            'name' => 'Student',
            'status' => 'active',
        ]);
    }
}
