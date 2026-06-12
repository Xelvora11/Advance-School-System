<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\LoginHistory;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperAdminModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_school_with_manual_tracking_fields(): void
    {
        $superAdmin = $this->superAdmin();

        $this->actingAs($superAdmin)->post(route('super-admin.schools.store'), [
            'name' => 'Beacon Test School',
            'short_name' => 'BTS',
            'email' => 'office@beacon.test',
            'phone' => '0300-1234567',
            'address' => 'Main Road',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'academic_year' => '2026-2027',
            'status' => 'active',
            'account_status' => 'active',
            'plan_name' => 'Standard',
            'manual_payment_status' => 'pending',
            'start_date' => '2026-06-01',
            'end_date' => '2027-05-31',
            'internal_payment_note' => 'Payment expected after onboarding.',
            'internal_support_note' => 'Needs setup call.',
            'admin_name' => 'School Owner',
            'admin_email' => 'owner@beacon.test',
            'admin_phone' => '0300-2223333',
            'admin_password' => 'password123',
        ])->assertRedirect();

        $school = School::where('email', 'office@beacon.test')->firstOrFail();

        $this->assertDatabaseHas('schools', [
            'id' => $school->id,
            'plan_name' => 'Standard',
            'manual_payment_status' => 'pending',
            'internal_support_note' => 'Needs setup call.',
        ]);

        $this->assertDatabaseHas('users', [
            'school_id' => $school->id,
            'email' => 'owner@beacon.test',
            'role' => User::ROLE_SCHOOL_ADMIN,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'school_id' => $school->id,
            'user_id' => $superAdmin->id,
            'role' => User::ROLE_SUPER_ADMIN,
            'ip_address' => '127.0.0.1',
            'action' => 'school_created',
        ]);
    }

    public function test_super_admin_school_pages_render(): void
    {
        $superAdmin = $this->superAdmin();
        $school = School::create(['name' => 'Render Test School']);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.dashboard'))
            ->assertOk()
            ->assertSee('Render Test School');

        $this->actingAs($superAdmin)
            ->get(route('super-admin.schools.index'))
            ->assertOk()
            ->assertSee('Render Test School');

        $this->actingAs($superAdmin)
            ->get(route('super-admin.schools.show', $school))
            ->assertOk()
            ->assertSee('Render Test School')
            ->assertSee('No recent login activity.');
    }

    public function test_super_admin_can_toggle_school_status_and_add_internal_note(): void
    {
        $superAdmin = $this->superAdmin();
        $school = School::create(['name' => 'Toggle School', 'setup_completed' => true]);

        $this->actingAs($superAdmin)
            ->patch(route('super-admin.schools.status', $school))
            ->assertRedirect();

        $this->assertFalse($school->fresh()->isActive());
        $this->assertSame('suspended', $school->fresh()->account_status);
        $this->assertDatabaseHas('activity_logs', [
            'school_id' => $school->id,
            'action' => 'school_deactivated',
        ]);

        $this->actingAs($superAdmin)
            ->post(route('super-admin.schools.notes.store', $school), [
                'note' => 'Owner asked for onboarding call.',
            ])->assertRedirect();

        $this->assertDatabaseHas('school_internal_notes', [
            'school_id' => $school->id,
            'user_id' => $superAdmin->id,
            'note' => 'Owner asked for onboarding call.',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'school_id' => $school->id,
            'action' => 'school_internal_note_added',
        ]);
    }

    public function test_super_admin_can_auto_generate_school_admin_password(): void
    {
        $superAdmin = $this->superAdmin();
        $school = School::create(['name' => 'Password Reset School']);
        $admin = User::create([
            'school_id' => $school->id,
            'name' => 'School Admin',
            'email' => 'school-admin@example.com',
            'password' => Hash::make('old-password'),
            'role' => User::ROLE_SCHOOL_ADMIN,
            'is_active' => true,
        ]);

        $response = $this->actingAs($superAdmin)
            ->patch(route('super-admin.schools.admins.password', [$school, $admin]), [
                'auto_generate' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('generated_password');

        $generatedPassword = $response->baseResponse->getSession()->get('generated_password');

        $this->assertNotSame('old-password', $generatedPassword);
        $this->assertTrue(Hash::check($generatedPassword, $admin->fresh()->password));
        $this->assertDatabaseHas('activity_logs', [
            'school_id' => $school->id,
            'user_id' => $superAdmin->id,
            'role' => User::ROLE_SUPER_ADMIN,
            'action' => 'school_admin_password_reset',
        ]);
    }

    public function test_activity_page_filters_by_school_and_action(): void
    {
        $superAdmin = $this->superAdmin();
        $school = School::create(['name' => 'Filtered School']);
        $otherSchool = School::create(['name' => 'Other School']);

        ActivityLog::create([
            'school_id' => $school->id,
            'user_id' => $superAdmin->id,
            'action' => 'school_updated',
            'description' => 'Visible activity',
        ]);
        ActivityLog::create([
            'school_id' => $otherSchool->id,
            'user_id' => $superAdmin->id,
            'action' => 'notice_published',
            'description' => 'Hidden activity',
        ]);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.activity', ['school_id' => $school->id, 'action' => 'school_updated']))
            ->assertOk()
            ->assertSee('Visible activity')
            ->assertDontSee('Hidden activity');
    }

    public function test_school_detail_handles_recent_login_without_linked_user(): void
    {
        $superAdmin = $this->superAdmin();
        $school = School::create(['name' => 'Login History School']);

        LoginHistory::create([
            'school_id' => $school->id,
            'user_id' => null,
            'ip_address' => '10.0.0.9',
            'logged_in_at' => now(),
        ]);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.schools.show', $school))
            ->assertOk()
            ->assertSee('Unknown user')
            ->assertSee('No linked email')
            ->assertSee('10.0.0.9');
    }

    public function test_missing_school_detail_redirects_with_friendly_error(): void
    {
        $superAdmin = $this->superAdmin();

        $this->actingAs($superAdmin)
            ->get(route('super-admin.schools.show', 999999))
            ->assertRedirect(route('super-admin.schools.index', absolute: false))
            ->assertSessionHasErrors(['school' => 'School account was not found.']);
    }

    private function superAdmin(): User
    {
        return User::create([
            'name' => 'Platform Owner',
            'email' => 'owner@example.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_SUPER_ADMIN,
            'is_active' => true,
        ]);
    }
}
