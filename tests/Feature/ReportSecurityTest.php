<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_csv_export_escapes_spreadsheet_formula_cells(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
        $employeeUser = User::factory()->create([
            'name' => '=HYPERLINK("https://example.test")',
            'role' => 'employee',
            'is_active' => true,
        ]);
        $employee = Employee::create([
            'user_id' => $employeeUser->id,
            'employee_number' => '+620001',
            'employment_status' => 'permanent',
            'attendance_enabled' => true,
        ]);
        AttendanceRecord::create([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-07-16',
            'status' => 'present',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.reports.csv', ['month' => '2026-07']))
            ->assertOk();

        $content = $response->streamedContent();
        $this->assertStringContainsString("'+620001", $content);
        $this->assertStringContainsString("'=HYPERLINK", $content);
        $this->assertStringNotContainsString(',+620001,', $content);
        $this->assertStringContainsString('Jam Masuk', $content);
        $this->assertStringContainsString(',Hadir,0,0,0', $content);

        AttendanceRecord::create([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-07-17',
            'status' => 'permission',
            'late_minutes' => 0,
            'early_leave_minutes' => 0,
            'work_duration_minutes' => 0,
        ]);

        $permissionContent = $this->actingAs($admin)
            ->get(route('admin.reports.csv', ['month' => '2026-07']))
            ->streamedContent();

        $this->assertStringContainsString(',Izin,-,-,-', $permissionContent);
    }
}
