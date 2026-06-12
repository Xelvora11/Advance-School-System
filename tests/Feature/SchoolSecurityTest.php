<?php

namespace Tests\Feature;

use App\Models\Guardian;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SchoolSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_login_redirects_to_super_admin_dashboard(): void
    {
        User::create([
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_SUPER_ADMIN,
            'is_active' => true,
        ]);

        $this->post(route('login'), [
            'email' => 'owner@example.com',
            'password' => 'password123',
        ])->assertRedirect(route('super-admin.dashboard', absolute: false));

        $this->assertDatabaseHas('login_histories', ['ip_address' => '127.0.0.1']);
    }

    public function test_inactive_school_user_cannot_login(): void
    {
        $school = School::create(['name' => 'Inactive School', 'status' => 'inactive', 'account_status' => 'inactive']);

        User::create([
            'school_id' => $school->id,
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_SCHOOL_ADMIN,
            'is_active' => true,
        ]);

        $this->post(route('login'), [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ])->assertSessionHasErrors([
            'email' => 'Your school account is inactive. Please contact school administration or platform support.',
        ]);

        $this->assertGuest();
    }

    public function test_school_admin_cannot_view_another_schools_student(): void
    {
        $schoolOne = School::create(['name' => 'School One', 'setup_completed' => true]);
        $schoolTwo = School::create(['name' => 'School Two', 'setup_completed' => true]);

        $admin = User::create([
            'school_id' => $schoolOne->id,
            'name' => 'School Admin',
            'email' => 'school@example.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_SCHOOL_ADMIN,
            'is_active' => true,
        ]);

        $class = SchoolClass::create(['school_id' => $schoolTwo->id, 'name' => 'Class 1']);
        $student = Student::create([
            'school_id' => $schoolTwo->id,
            'school_class_id' => $class->id,
            'registration_number' => 'REG-2026-0001',
            'name' => 'Other Student',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get(route('students.show', $student))
            ->assertNotFound();
    }

    public function test_parent_can_only_view_linked_children(): void
    {
        $school = School::create(['name' => 'Parent School']);
        $class = SchoolClass::create(['school_id' => $school->id, 'name' => 'Class 1']);

        $parentUser = User::create([
            'school_id' => $school->id,
            'name' => 'Parent',
            'email' => 'parent@example.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_PARENT,
            'is_active' => true,
        ]);

        $linkedStudent = Student::create([
            'school_id' => $school->id,
            'school_class_id' => $class->id,
            'registration_number' => 'REG-2026-0001',
            'name' => 'Linked Student',
            'status' => 'active',
        ]);

        $otherStudent = Student::create([
            'school_id' => $school->id,
            'school_class_id' => $class->id,
            'registration_number' => 'REG-2026-0002',
            'name' => 'Other Student',
            'status' => 'active',
        ]);

        $guardian = Guardian::create([
            'school_id' => $school->id,
            'user_id' => $parentUser->id,
            'name' => 'Parent',
            'email' => 'parent@example.com',
            'status' => 'active',
        ]);
        $guardian->students()->attach($linkedStudent->id);

        $this->actingAs($parentUser)
            ->get(route('parent.students.show', $linkedStudent))
            ->assertOk();

        $this->actingAs($parentUser)
            ->get(route('parent.students.show', $otherStudent))
            ->assertNotFound();
    }
}
