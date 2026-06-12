<?php

namespace Tests\Feature;

use App\Models\Admission;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdmissionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_convert_approved_admission_to_student(): void
    {
        $school = School::create(['name' => 'Workflow School', 'setup_completed' => true]);
        $class = SchoolClass::create(['school_id' => $school->id, 'name' => 'Class 1']);
        $section = Section::create(['school_id' => $school->id, 'school_class_id' => $class->id, 'name' => 'A']);
        $admin = User::create([
            'school_id' => $school->id,
            'name' => 'Admin',
            'email' => 'admin@workflow.test',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_SCHOOL_ADMIN,
            'is_active' => true,
        ]);

        $admission = Admission::create([
            'school_id' => $school->id,
            'requested_class_id' => $class->id,
            'student_name' => 'New Student',
            'guardian_name' => 'Guardian',
            'guardian_phone' => '0300-0000000',
            'status' => 'approved',
        ]);

        $this->actingAs($admin)
            ->post(route('admissions.convert', $admission), [
                'section_id' => $section->id,
                'roll_number' => '12',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('students', [
            'school_id' => $school->id,
            'section_id' => $section->id,
            'name' => 'New Student',
            'roll_number' => '12',
            'guardian_phone' => '0300-0000000',
        ]);

        $this->assertDatabaseHas('admissions', [
            'id' => $admission->id,
            'status' => 'enrolled',
        ]);

        $this->actingAs($admin)
            ->post(route('admissions.convert', $admission->fresh()))
            ->assertSessionHasErrors('admission');

        $this->assertDatabaseCount('students', 1);
    }
}
