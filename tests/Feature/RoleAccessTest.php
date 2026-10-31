<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_cannot_open_admin_area(): void
    {
        $employee = User::factory()->create([
            'role' => 'employee',
            'is_active' => true,
        ]);

        $this->actingAs($employee)
            ->get('/admin/dashboard')
            ->assertForbidden();
    }

    public function test_admin_cannot_open_employee_area(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get('/pegawai/dashboard')
            ->assertForbidden();
    }

    public function test_employee_is_redirected_from_shared_login_to_employee_dashboard(): void
    {
        User::factory()->create([
            'email' => 'pegawai@example.test',
            'password' => 'password',
            'role' => 'employee',
            'is_active' => true,
        ]);

        $this->post('/login', [
            'email' => 'pegawai@example.test',
            'password' => 'password',
        ])->assertRedirect(route('employee.dashboard'));

        $this->assertAuthenticated();
    }

    public function test_admin_is_redirected_from_shared_login_to_admin_dashboard(): void
    {
        User::factory()->create([
            'email' => 'admin@example.test',
            'password' => 'password',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->post('/login', [
            'email' => 'admin@example.test',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticated();
    }

    public function test_inactive_account_is_rejected_from_shared_login(): void
    {
        User::factory()->create([
            'email' => 'inactive@example.test',
            'password' => 'password',
            'role' => 'employee',
            'is_active' => false,
        ]);

        $this->post('/login', [
            'email' => 'inactive@example.test',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
