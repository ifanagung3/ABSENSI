<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Shift;
use App\Models\User;
use App\Models\WorkLocation;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttendanceFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_employee_can_check_in_inside_geofence(): void
    {
        Storage::fake('local');
        [$user] = $this->makeScheduledEmployee();

        $response = $this->actingAs($user)->postJson(route('employee.attendance.store'), [
            'event_type' => 'check_in',
            'photo' => $this->fakeSelfie(),
            'latitude' => -0.6447000,
            'longitude' => 100.1401000,
            'location_accuracy' => 10,
            'device_time' => '2026-07-13T07:20:00+07:00',
            'timezone' => 'Asia/Jakarta',
            'device_id' => 'test-device',
        ]);

        $response->assertOk()->assertJsonPath('attendance.status', 'present');
        $this->assertDatabaseHas('attendance_events', [
            'event_type' => 'check_in',
            'inside_geofence' => true,
            'validation_status' => 'valid',
        ]);
    }

    public function test_employee_is_rejected_outside_geofence(): void
    {
        Storage::fake('local');
        [$user] = $this->makeScheduledEmployee();

        $response = $this->actingAs($user)->postJson(route('employee.attendance.store'), [
            'event_type' => 'check_in',
            'photo' => $this->fakeSelfie(),
            'latitude' => -0.6400000,
            'longitude' => 100.1401000,
            'location_accuracy' => 10,
            'device_time' => '2026-07-13T07:20:00+07:00',
            'timezone' => 'Asia/Jakarta',
            'device_id' => 'test-device',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('location');
        $this->assertDatabaseCount('attendance_events', 0);
    }

    public function test_employee_must_check_in_before_check_out(): void
    {
        Storage::fake('local');
        [$user] = $this->makeScheduledEmployee();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-13 15:10:00', 'Asia/Jakarta'));

        $response = $this->actingAs($user)->postJson(route('employee.attendance.store'), [
            'event_type' => 'check_out',
            'photo' => $this->fakeSelfie(),
            'latitude' => -0.6447000,
            'longitude' => 100.1401000,
            'location_accuracy' => 10,
            'device_time' => '2026-07-13T15:10:00+07:00',
            'timezone' => 'Asia/Jakarta',
            'device_id' => 'test-device',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('event_type');
        $this->assertDatabaseCount('attendance_events', 0);
    }

    public function test_employee_can_check_out_after_check_in(): void
    {
        Storage::fake('local');
        [$user] = $this->makeScheduledEmployee();

        $this->actingAs($user)->postJson(route('employee.attendance.store'), [
            'event_type' => 'check_in',
            'photo' => $this->fakeSelfie(),
            'latitude' => -0.6447000,
            'longitude' => 100.1401000,
            'location_accuracy' => 10,
            'device_time' => '2026-07-13T07:20:00+07:00',
            'timezone' => 'Asia/Jakarta',
            'device_id' => 'test-device',
        ])->assertOk();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-13 15:35:00', 'Asia/Jakarta'));
        $response = $this->actingAs($user)->postJson(route('employee.attendance.store'), [
            'event_type' => 'check_out',
            'photo' => $this->fakeSelfie(),
            'latitude' => -0.6447000,
            'longitude' => 100.1401000,
            'location_accuracy' => 10,
            'device_time' => '2026-07-13T15:35:00+07:00',
            'timezone' => 'Asia/Jakarta',
            'device_id' => 'test-device',
        ]);

        $response->assertOk()->assertJsonPath('attendance.checked_out_at', '15:35');
        $this->assertDatabaseHas('attendance_events', ['event_type' => 'check_out']);
        $this->assertDatabaseHas('attendance_records', ['work_duration_minutes' => 495]);
    }

    public function test_dashboard_keeps_checkout_action_after_midnight_within_grace_period(): void
    {
        Storage::fake('local');
        [$user] = $this->makeScheduledEmployee();

        $this->actingAs($user)->postJson(route('employee.attendance.store'), [
            'event_type' => 'check_in',
            'photo' => $this->fakeSelfie(),
            'latitude' => -0.6447000,
            'longitude' => 100.1401000,
            'location_accuracy' => 10,
            'device_time' => '2026-07-13T07:20:00+07:00',
            'timezone' => 'Asia/Jakarta',
            'device_id' => 'test-device',
        ])->assertOk();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-14 01:00:00', 'Asia/Jakarta'));

        $this->actingAs($user)
            ->get(route('employee.dashboard'))
            ->assertOk()
            ->assertSee('Absen Pulang')
            ->assertSee('type=check_out', false);
    }

    private function fakeSelfie(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'hadirin-selfie-');
        file_put_contents(
            $path,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9ZQmcAAAAASUVORK5CYII=', true)
        );

        return new UploadedFile($path, 'selfie.png', 'image/png', null, true);
    }

    private function makeScheduledEmployee(): array
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-13 07:20:00', 'Asia/Jakarta'));

        $user = User::create([
            'name' => 'Pegawai Test',
            'email' => 'pegawai-test@example.test',
            'password' => 'password',
            'role' => 'employee',
            'is_active' => true,
        ]);

        $location = WorkLocation::create([
            'name' => 'SMKN 4 Pariaman Test',
            'address' => 'Pariaman',
            'latitude' => -0.6447000,
            'longitude' => 100.1401000,
            'radius_meters' => 100,
            'maximum_accuracy_meters' => 100,
            'is_active' => true,
        ]);

        $shift = Shift::create([
            'name' => 'Shift Test',
            'start_time' => '07:30',
            'end_time' => '15:30',
            'check_in_open_minutes' => 90,
            'late_tolerance_minutes' => 10,
            'check_out_open_minutes' => 30,
            'crosses_midnight' => false,
        ]);

        $employee = Employee::create([
            'user_id' => $user->id,
            'default_location_id' => $location->id,
            'employee_number' => 'TEST-001',
            'employment_status' => 'permanent',
            'attendance_enabled' => true,
        ]);

        EmployeeSchedule::create([
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'work_location_id' => $location->id,
            'work_date' => '2026-07-13',
            'status' => 'working',
        ]);

        return [$user, $employee, $location, $shift];
    }
}
