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

class MessageModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_message_parent_inside_same_school(): void
    {
        [$school, $admin, $parent] = $this->schoolWithAdminAndParent();

        $this->actingAs($admin)->post(route('messages.store'), [
            'recipient_id' => $parent->id,
            'subject' => 'Fee reminder',
            'body' => 'Please review the latest challan.',
        ])->assertRedirect();

        $this->assertDatabaseHas('message_threads', [
            'school_id' => $school->id,
            'subject' => 'Fee reminder',
            'created_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('messages', [
            'school_id' => $school->id,
            'sender_id' => $admin->id,
            'body' => 'Please review the latest challan.',
        ]);

        $this->actingAs($parent)
            ->get(route('messages.index'))
            ->assertOk()
            ->assertSee('Fee reminder')
            ->assertSee('1 unread');
    }

    public function test_user_cannot_message_recipient_from_another_school(): void
    {
        [, $admin] = $this->schoolWithAdminAndParent('one');
        [, , $otherParent] = $this->schoolWithAdminAndParent('two');

        $this->actingAs($admin)->post(route('messages.store'), [
            'recipient_id' => $otherParent->id,
            'subject' => 'Invalid',
            'body' => 'This should not send.',
        ])->assertSessionHasErrors('recipient_id');

        $this->assertDatabaseMissing('messages', ['body' => 'This should not send.']);
    }

    public function test_teacher_to_parent_messages_follow_school_setting_and_assignment(): void
    {
        [$school, , $parent, $class, $section] = $this->schoolWithAdminAndParent();
        $teacherUser = User::create([
            'school_id' => $school->id,
            'name' => 'Teacher',
            'email' => 'teacher@example.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_TEACHER,
            'is_active' => true,
        ]);
        $teacher = Teacher::create([
            'school_id' => $school->id,
            'user_id' => $teacherUser->id,
            'name' => 'Teacher',
            'email' => 'teacher@example.com',
            'status' => 'active',
        ]);
        TeacherAssignment::create([
            'school_id' => $school->id,
            'teacher_id' => $teacher->id,
            'school_class_id' => $class->id,
            'section_id' => $section->id,
        ]);

        $this->actingAs($teacherUser)->post(route('messages.store'), [
            'recipient_id' => $parent->id,
            'subject' => 'Homework',
            'body' => 'Default settings should block this.',
        ])->assertSessionHasErrors('recipient_id');

        MessageSetting::forSchoolId($school->id)->update(['allow_teacher_to_parent_messages' => true]);

        $this->actingAs($teacherUser)->post(route('messages.store'), [
            'recipient_id' => $parent->id,
            'subject' => 'Class update',
            'body' => 'Please note the class update.',
        ])->assertRedirect();

        $this->assertDatabaseHas('messages', [
            'school_id' => $school->id,
            'sender_id' => $teacherUser->id,
            'body' => 'Please note the class update.',
        ]);
    }

    private function schoolWithAdminAndParent(string $suffix = 'main'): array
    {
        $school = School::create(['name' => 'Message School '.$suffix, 'setup_completed' => true]);
        $class = SchoolClass::create(['school_id' => $school->id, 'name' => 'Class 1']);
        $section = Section::create(['school_id' => $school->id, 'school_class_id' => $class->id, 'name' => 'A']);

        $admin = User::create([
            'school_id' => $school->id,
            'name' => 'School Admin '.$suffix,
            'email' => 'admin-'.$suffix.'@example.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_SCHOOL_ADMIN,
            'is_active' => true,
        ]);

        $parent = User::create([
            'school_id' => $school->id,
            'name' => 'Parent '.$suffix,
            'email' => 'parent-'.$suffix.'@example.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_PARENT,
            'is_active' => true,
        ]);

        $student = Student::create([
            'school_id' => $school->id,
            'school_class_id' => $class->id,
            'section_id' => $section->id,
            'registration_number' => 'REG-'.$suffix,
            'name' => 'Student '.$suffix,
            'status' => 'active',
        ]);

        $guardian = Guardian::create([
            'school_id' => $school->id,
            'user_id' => $parent->id,
            'name' => 'Parent '.$suffix,
            'email' => $parent->email,
            'status' => 'active',
        ]);
        $guardian->students()->attach($student->id);

        MessageSetting::forSchoolId($school->id);

        return [$school, $admin, $parent, $class, $section];
    }
}
