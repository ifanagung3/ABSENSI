<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecuritySessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_temporary_password_account_is_sent_to_profile(): void
    {
        User::factory()->create([
            'email' => 'temporary-admin@example.test',
            'password' => 'Password123',
            'role' => 'admin',
            'is_active' => true,
            'must_change_password' => true,
        ]);

        $this->post('/login', [
            'email' => 'temporary-admin@example.test',
            'password' => 'Password123',
        ])->assertRedirect(route('admin.profile.edit'))
            ->assertSessionHas('auth_session_version', 1);
    }

    public function test_old_session_is_rejected_after_session_version_changes(): void
    {
        [$user] = $this->employee();
        $oldVersion = (int) $user->session_version;
        $user->update(['session_version' => $oldVersion + 1]);

        $this->be($user->fresh());
        $this->withSession(['auth_session_version' => $oldVersion])
            ->get(route('employee.dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_temporary_password_cannot_bypass_profile_change_screen(): void
    {
        [$user] = $this->employee(['must_change_password' => true]);

        $this->actingAs($user)
            ->get(route('employee.dashboard'))
            ->assertRedirect(route('employee.profile.edit'))
            ->assertSessionHas('warning');
    }

    public function test_employee_password_change_rotates_session_and_clears_temporary_flag(): void
    {
        [$user] = $this->employee(['must_change_password' => true]);

        $this->actingAs($user)
            ->put(route('employee.profile.password'), [
                'current_password' => 'Password123',
                'password' => 'Password456',
                'password_confirmation' => 'Password456',
            ])
            ->assertRedirect()
            ->assertSessionHas('auth_session_version', 2);

        $user->refresh();
        $this->assertFalse($user->must_change_password);
        $this->assertSame(2, $user->session_version);
        $this->assertTrue(Hash::check('Password456', $user->password));
    }

    public function test_password_longer_than_bcrypt_limit_is_rejected(): void
    {
        [$user] = $this->employee();
        $password = str_repeat('Ab1', 25);

        $this->actingAs($user)
            ->put(route('employee.profile.password'), [
                'current_password' => 'Password123',
                'password' => $password,
                'password_confirmation' => $password,
            ])
            ->assertSessionHasErrors('password');

        $this->assertSame(1, $user->fresh()->session_version);
    }

    public function test_admin_password_reset_revokes_employee_sessions_and_forces_change(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
        [$user, $employee] = $this->employee();

        $this->actingAs($admin)
            ->put(route('admin.employees.reset-password', $employee), [
                'password' => 'ResetPass123',
                'password_confirmation' => 'ResetPass123',
            ])
            ->assertRedirect();

        $user->refresh();
        $this->assertSame(2, $user->session_version);
        $this->assertTrue($user->must_change_password);
        $this->assertTrue(Hash::check('ResetPass123', $user->password));
    }

    private function employee(array $userAttributes = []): array
    {
        $user = User::factory()->create(array_merge([
            'name' => 'Pegawai Security',
            'email' => 'security-employee@example.test',
            'password' => 'Password123',
            'role' => 'employee',
            'is_active' => true,
        ], $userAttributes));
        $employee = Employee::create([
            'user_id' => $user->id,
            'employee_number' => 'SEC-001',
            'employment_status' => 'permanent',
            'attendance_enabled' => true,
        ]);

        return [$user, $employee];
    }
}
