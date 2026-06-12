<?php

namespace Tests\Feature;

use App\Models\Guardian;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentGuardianTest extends TestCase
{
    use RefreshDatabase;

    public function test_guardians_without_email_are_not_merged_by_null_email(): void
    {
        [$admin, $class] = $this->schoolAdminAndClass();

        $this->actingAs($admin)->post(route('students.store'), [
            'name' => 'Student One',
            'school_class_id' => $class->id,
            'status' => 'active',
            'guardian_name' => 'Guardian One',
            'guardian_phone' => '0300-1111111',
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('students.store'), [
            'name' => 'Student Two',
            'school_class_id' => $class->id,
            'status' => 'active',
            'guardian_name' => 'Guardian Two',
            'guardian_phone' => '0300-2222222',
        ])->assertRedirect();

        $this->assertSame(2, Guardian::count());
        $this->assertDatabaseHas('parents', ['name' => 'Guardian One', 'phone' => '0300-1111111']);
        $this->assertDatabaseHas('parents', ['name' => 'Guardian Two', 'phone' => '0300-2222222']);
    }

    public function test_new_parent_login_requires_explicit_secure_password(): void
    {
        [$admin, $class] = $this->schoolAdminAndClass();

        $this->actingAs($admin)->post(route('students.store'), [
            'name' => 'Student With Parent',
            'school_class_id' => $class->id,
            'status' => 'active',
            'guardian_name' => 'Parent User',
            'guardian_email' => 'parent-login@example.com',
            'create_parent_login' => '1',
        ])->assertSessionHasErrors('parent_password');

        $this->assertDatabaseMissing('users', ['email' => 'parent-login@example.com']);
    }

    private function schoolAdminAndClass(): array
    {
        $school = School::create(['name' => 'Guardian School', 'setup_completed' => true]);
        $class = SchoolClass::create(['school_id' => $school->id, 'name' => 'Class 1']);

        $admin = User::create([
            'school_id' => $school->id,
            'name' => 'School Admin',
            'email' => 'guardian-admin@example.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_SCHOOL_ADMIN,
            'is_active' => true,
        ]);

        return [$admin, $class];
    }
}
