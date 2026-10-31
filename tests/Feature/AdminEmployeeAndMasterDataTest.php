<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use App\Models\WorkLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminEmployeeAndMasterDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_employee_with_forced_temporary_password_flow(): void
    {
        $admin = $this->admin();
        $department = Department::create(['name' => 'Kurikulum']);
        $position = Position::create(['name' => 'Staf']);
        $location = $this->location();

        $this->actingAs($admin)
            ->post(route('admin.employees.store'), [
                'name' => 'Pegawai Baru',
                'email' => 'pegawai-baru@example.test',
                'employee_number' => 'PGW-0099',
                'department_id' => $department->id,
                'position_id' => $position->id,
                'default_location_id' => $location->id,
                'phone' => '081200000099',
                'join_date' => '2026-07-16',
                'employment_status' => 'permanent',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $user = User::where('email', 'pegawai-baru@example.test')->firstOrFail();
        $this->assertTrue($user->must_change_password);
        $this->assertDatabaseHas('employees', [
            'user_id' => $user->id,
            'employee_number' => 'PGW-0099',
            'default_location_id' => $location->id,
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'employee.created']);
    }

    public function test_employee_edit_validation_returns_to_the_correct_modal(): void
    {
        $admin = $this->admin();
        $employeeUser = User::factory()->create([
            'role' => 'employee',
            'is_active' => true,
        ]);
        $employee = Employee::create([
            'user_id' => $employeeUser->id,
            'employee_number' => 'PGW-0100',
            'employment_status' => 'permanent',
            'attendance_enabled' => true,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.employees.update', $employee), [
                'name' => '',
                'email' => 'invalid-email',
                'employee_number' => '',
                'employment_status' => 'permanent',
            ])
            ->assertRedirect()
            ->assertSessionHasErrorsIn('updateEmployee', ['name', 'email', 'employee_number'])
            ->assertSessionHas('editing_employee_id', $employee->id);
    }

    public function test_inactive_location_cannot_be_assigned_to_new_employee(): void
    {
        $admin = $this->admin();
        $location = $this->location(['is_active' => false]);

        $this->actingAs($admin)
            ->post(route('admin.employees.store'), [
                'name' => 'Pegawai Lokasi',
                'email' => 'lokasi@example.test',
                'employee_number' => 'PGW-0101',
                'default_location_id' => $location->id,
                'employment_status' => 'permanent',
            ])
            ->assertSessionHasErrorsIn('createEmployee', 'default_location_id');
    }

    public function test_master_data_validation_uses_specific_error_bags(): void
    {
        $admin = $this->admin();
        $department = Department::create(['name' => 'Tata Usaha']);
        $position = Position::create(['name' => 'Staf']);

        $this->actingAs($admin)
            ->post(route('admin.master-data.departments.store'), ['name' => ''])
            ->assertSessionHasErrorsIn('createDepartment', 'name');

        $this->actingAs($admin)
            ->patch(route('admin.master-data.positions.update', $position), [
                'name' => '',
            ])
            ->assertSessionHasErrorsIn('updatePosition', 'name')
            ->assertSessionHas('editing_position_id', $position->id);

        $this->actingAs($admin)
            ->delete(route('admin.master-data.departments.destroy', $department))
            ->assertRedirect();
        $this->assertDatabaseMissing('departments', ['id' => $department->id]);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    private function location(array $attributes = []): WorkLocation
    {
        return WorkLocation::create(array_merge([
            'name' => 'Lokasi Test '.str()->uuid(),
            'address' => 'Alamat test',
            'latitude' => -0.6447,
            'longitude' => 100.1401,
            'radius_meters' => 100,
            'maximum_accuracy_meters' => 100,
            'is_active' => true,
        ], $attributes));
    }
}
