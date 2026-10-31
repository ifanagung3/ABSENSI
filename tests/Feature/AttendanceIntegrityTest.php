<?php

namespace Tests\Feature;

use App\Models\AttendanceEvent;
use App\Models\AttendanceRecord;
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

class AttendanceIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_single_schedule_cannot_mutate_a_schedule_with_attendance(): void
    {
        $admin = $this->makeAdmin();
        [, $employee, $location, , $schedule] = $this->makeScheduledEmployee();
        $replacement = $this->makeShift(['name' => 'Shift Pengganti']);
        AttendanceRecord::create([
            'employee_id' => $employee->id,
            'schedule_id' => $schedule->id,
            'attendance_date' => '2026-07-13',
            'status' => 'present',
        ]);

        $this->actingAs($admin)->post(route('admin.schedules.store'), [
            'employee_id' => $employee->id,
            'shift_id' => $replacement->id,
            'work_location_id' => $location->id,
            'work_date' => '2026-07-13',
            'status' => 'working',
        ])->assertRedirect()
            ->assertSessionHasErrorsIn('singleSchedule', ['schedule']);

        $this->assertDatabaseHas('employee_schedules', [
            'id' => $schedule->id,
            'shift_id' => $schedule->shift_id,
        ]);
    }

    public function test_bulk_schedule_is_atomic_when_one_target_has_attendance(): void
    {
        $admin = $this->makeAdmin();
        [, $firstEmployee, $location, $shift, $firstSchedule] = $this->makeScheduledEmployee();
        [, $secondEmployee, , , $secondSchedule] = $this->makeScheduledEmployee();
        $replacement = $this->makeShift(['name' => 'Shift Massal Baru']);
        AttendanceRecord::create([
            'employee_id' => $firstEmployee->id,
            'schedule_id' => $firstSchedule->id,
            'attendance_date' => '2026-07-13',
            'status' => 'present',
        ]);

        $this->actingAs($admin)->post(route('admin.schedules.bulk-store'), [
            'employee_ids' => [$firstEmployee->id, $secondEmployee->id],
            'shift_id' => $replacement->id,
            'work_location_id' => $location->id,
            'start_date' => '2026-07-13',
            'end_date' => '2026-07-13',
            'status' => 'working',
        ])->assertRedirect()
            ->assertSessionHasErrorsIn('bulkSchedule', ['schedule']);

        $this->assertDatabaseHas('employee_schedules', [
            'id' => $firstSchedule->id,
            'shift_id' => $shift->id,
        ]);
        $this->assertDatabaseHas('employee_schedules', [
            'id' => $secondSchedule->id,
            'shift_id' => $secondSchedule->shift_id,
        ]);
    }

    public function test_bulk_schedule_upsert_is_sqlite_safe_and_bounded(): void
    {
        $admin = $this->makeAdmin();
        [, $employee, $location, , $schedule] = $this->makeScheduledEmployee();
        $replacement = $this->makeShift(['name' => 'Shift Upsert']);

        config(['attendance.maximum_bulk_schedule_days' => 2]);
        $this->actingAs($admin)->post(route('admin.schedules.bulk-store'), [
            'employee_ids' => [$employee->id],
            'shift_id' => $replacement->id,
            'work_location_id' => $location->id,
            'start_date' => '2026-07-13',
            'end_date' => '2026-07-14',
            'status' => 'working',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseCount('employee_schedules', 2);
        $this->assertDatabaseHas('employee_schedules', [
            'id' => $schedule->id,
            'shift_id' => $replacement->id,
        ]);

        $this->actingAs($admin)->post(route('admin.schedules.bulk-store'), [
            'employee_ids' => [$employee->id],
            'shift_id' => $replacement->id,
            'work_location_id' => $location->id,
            'start_date' => '2026-07-13',
            'end_date' => '2026-07-15',
            'status' => 'working',
        ])->assertRedirect()
            ->assertSessionHasErrorsIn('bulkSchedule', ['end_date']);
    }

    public function test_bulk_schedule_limits_employee_count(): void
    {
        $admin = $this->makeAdmin();
        [, $firstEmployee, $location, $shift] = $this->makeScheduledEmployee();
        [, $secondEmployee] = $this->makeScheduledEmployee();
        config(['attendance.maximum_bulk_schedule_employees' => 1]);

        $this->actingAs($admin)->post(route('admin.schedules.bulk-store'), [
            'employee_ids' => [$firstEmployee->id, $secondEmployee->id],
            'shift_id' => $shift->id,
            'work_location_id' => $location->id,
            'start_date' => '2026-07-14',
            'end_date' => '2026-07-14',
            'status' => 'working',
        ])->assertRedirect()
            ->assertSessionHasErrorsIn('bulkSchedule', ['employee_ids']);
    }

    public function test_shift_requires_consistent_crosses_midnight_setting(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post(route('admin.shifts.store'), [
            'name' => 'Shift Malam Invalid',
            'start_time' => '22:00',
            'end_time' => '06:00',
            'check_in_open_minutes' => 60,
            'late_tolerance_minutes' => 10,
            'check_out_open_minutes' => 30,
        ])->assertRedirect()
            ->assertSessionHasErrorsIn('createShift', ['crosses_midnight']);

        $this->assertDatabaseMissing('shifts', ['name' => 'Shift Malam Invalid']);
    }

    public function test_shift_and_location_configuration_are_immutable_after_attendance(): void
    {
        $admin = $this->makeAdmin();
        [, $employee, $location, $shift, $schedule] = $this->makeScheduledEmployee();
        AttendanceRecord::create([
            'employee_id' => $employee->id,
            'schedule_id' => $schedule->id,
            'attendance_date' => '2026-07-13',
            'status' => 'present',
        ]);

        $this->actingAs($admin)->patch(route('admin.shifts.update', $shift), [
            'name' => 'Shift Berubah',
            'start_time' => '07:30',
            'end_time' => '15:30',
            'check_in_open_minutes' => 90,
            'late_tolerance_minutes' => 10,
            'check_out_open_minutes' => 30,
        ])->assertRedirect()
            ->assertSessionHasErrorsIn('updateShift', ['shift'])
            ->assertSessionHas('editing_shift_id', $shift->id);

        $this->actingAs($admin)->patch(route('admin.locations.update', $location), [
            'name' => 'Lokasi Berubah',
            'address' => 'Alamat baru',
            'latitude' => -0.6447000,
            'longitude' => 100.1401000,
            'radius_meters' => 200,
            'maximum_accuracy_meters' => 100,
        ])->assertRedirect()
            ->assertSessionHasErrorsIn('updateLocation', ['location'])
            ->assertSessionHas('editing_location_id', $location->id);

        $this->assertDatabaseHas('shifts', ['id' => $shift->id, 'name' => $shift->name]);
        $this->assertDatabaseHas('work_locations', [
            'id' => $location->id,
            'name' => $location->name,
        ]);
    }

    public function test_normal_shift_can_check_out_after_midnight_within_grace_period(): void
    {
        Storage::fake('local');
        [$user] = $this->makeScheduledEmployee();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-13 07:20:00', 'Asia/Jakarta'));

        $this->actingAs($user)->postJson(
            route('employee.attendance.store'),
            $this->attendancePayload('check_in', '2026-07-13T07:20:00+07:00', 'normal-in')
        )->assertOk();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-14 01:00:00', 'Asia/Jakarta'));
        $this->actingAs($user)->postJson(
            route('employee.attendance.store'),
            $this->attendancePayload('check_out', '2026-07-14T01:00:00+07:00', 'normal-out')
        )->assertOk()->assertJsonPath('attendance.checked_out_at', '01:00');
    }

    public function test_overnight_shift_can_check_out_after_shift_end_within_grace_period(): void
    {
        Storage::fake('local');
        [$user] = $this->makeScheduledEmployee([
            'start_time' => '22:00',
            'end_time' => '06:00',
            'crosses_midnight' => true,
        ]);
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-13 21:30:00', 'Asia/Jakarta'));

        $this->actingAs($user)->postJson(
            route('employee.attendance.store'),
            $this->attendancePayload('check_in', '2026-07-13T21:30:00+07:00', 'night-in')
        )->assertOk();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-14 17:00:00', 'Asia/Jakarta'));
        $this->actingAs($user)->postJson(
            route('employee.attendance.store'),
            $this->attendancePayload('check_out', '2026-07-14T17:00:00+07:00', 'night-out')
        )->assertOk()->assertJsonPath('attendance.checked_out_at', '17:00');
    }

    public function test_reused_photo_by_same_employee_requires_review(): void
    {
        Storage::fake('local');
        [$user] = $this->makeScheduledEmployee();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-13 07:20:00', 'Asia/Jakarta'));

        $this->actingAs($user)->postJson(
            route('employee.attendance.store'),
            $this->attendancePayload('check_in', '2026-07-13T07:20:00+07:00')
        )->assertOk();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-13 15:10:00', 'Asia/Jakarta'));
        $this->actingAs($user)->postJson(
            route('employee.attendance.store'),
            $this->attendancePayload('check_out', '2026-07-13T15:10:00+07:00')
        )->assertOk()->assertJsonPath('attendance.status', 'pending_review');

        $this->assertDatabaseHas('attendance_events', [
            'event_type' => 'check_out',
            'validation_status' => 'review',
            'rejection_reason' => 'Hash foto identik ditemukan pada absensi sebelumnya',
        ]);
    }

    public function test_device_evidence_is_required_and_timezone_must_be_valid(): void
    {
        Storage::fake('local');
        [$user] = $this->makeScheduledEmployee();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-13 07:20:00', 'Asia/Jakarta'));

        $payload = $this->attendancePayload('check_in', '2026-07-13T07:20:00+07:00');
        unset($payload['device_time'], $payload['device_id']);
        $payload['timezone'] = 'Zona/Tidak-Ada';

        $this->actingAs($user)->postJson(route('employee.attendance.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['device_time', 'timezone', 'device_id']);
    }

    public function test_admin_review_updates_event_and_late_minutes_consistently(): void
    {
        $admin = $this->makeAdmin();
        [, $employee, , , $schedule] = $this->makeScheduledEmployee();
        $record = AttendanceRecord::create([
            'employee_id' => $employee->id,
            'schedule_id' => $schedule->id,
            'attendance_date' => '2026-07-13',
            'status' => 'pending_review',
            'checked_in_at' => CarbonImmutable::parse('2026-07-13 08:00:00', 'Asia/Jakarta'),
        ]);
        $event = $this->makeReviewEvent($record);

        $this->actingAs($admin)->patch(route('admin.attendance.review', $record), [
            'status' => 'late',
            'notes' => 'Bukti sudah diperiksa.',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('attendance_records', [
            'id' => $record->id,
            'status' => 'late',
            'late_minutes' => 30,
            'corrected_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('attendance_events', [
            'id' => $event->id,
            'validation_status' => 'valid',
        ]);

        $this->actingAs($admin)->patch(route('admin.attendance.review', $record), [
            'status' => 'present',
        ])->assertRedirect()->assertSessionHasErrors(['attendance']);
    }

    public function test_reviewing_as_present_resets_late_minutes(): void
    {
        $admin = $this->makeAdmin();
        [, $employee, , , $schedule] = $this->makeScheduledEmployee();
        $record = AttendanceRecord::create([
            'employee_id' => $employee->id,
            'schedule_id' => $schedule->id,
            'attendance_date' => '2026-07-13',
            'status' => 'pending_review',
            'checked_in_at' => CarbonImmutable::parse('2026-07-13 08:00:00', 'Asia/Jakarta'),
            'late_minutes' => 30,
        ]);
        $this->makeReviewEvent($record);

        $this->actingAs($admin)->patch(route('admin.attendance.review', $record), [
            'status' => 'present',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('attendance_records', [
            'id' => $record->id,
            'status' => 'present',
            'late_minutes' => 0,
        ]);
    }

    public function test_close_day_command_is_idempotent(): void
    {
        [, , , , $schedule] = $this->makeScheduledEmployee();

        $this->artisan('attendance:close-day 2026-07-13')->assertSuccessful();
        $this->artisan('attendance:close-day 2026-07-13')->assertSuccessful();

        $this->assertDatabaseCount('attendance_records', 1);
        $this->assertDatabaseHas('attendance_records', [
            'schedule_id' => $schedule->id,
            'status' => 'absent',
        ]);
    }

    private function makeAdmin(): User
    {
        $this->sequence++;

        return User::create([
            'name' => 'Admin Test',
            'email' => "admin-integrity-{$this->sequence}@example.test",
            'password' => 'password',
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    private function makeScheduledEmployee(array $shiftOverrides = []): array
    {
        $this->sequence++;
        $suffix = $this->sequence;
        $user = User::create([
            'name' => "Pegawai {$suffix}",
            'email' => "pegawai-integrity-{$suffix}@example.test",
            'password' => 'password',
            'role' => 'employee',
            'is_active' => true,
        ]);
        $location = WorkLocation::create([
            'name' => "Lokasi {$suffix}",
            'address' => 'Pariaman',
            'latitude' => -0.6447000,
            'longitude' => 100.1401000,
            'radius_meters' => 100,
            'maximum_accuracy_meters' => 100,
            'is_active' => true,
        ]);
        $shift = $this->makeShift($shiftOverrides + ['name' => "Shift {$suffix}"]);
        $employee = Employee::create([
            'user_id' => $user->id,
            'default_location_id' => $location->id,
            'employee_number' => "INT-{$suffix}",
            'employment_status' => 'permanent',
            'attendance_enabled' => true,
        ]);
        $schedule = EmployeeSchedule::create([
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'work_location_id' => $location->id,
            'work_date' => '2026-07-13',
            'status' => 'working',
        ]);

        return [$user, $employee, $location, $shift, $schedule];
    }

    private function makeShift(array $overrides = []): Shift
    {
        $this->sequence++;

        return Shift::create($overrides + [
            'name' => "Shift Helper {$this->sequence}",
            'start_time' => '07:30',
            'end_time' => '15:30',
            'check_in_open_minutes' => 90,
            'late_tolerance_minutes' => 10,
            'check_out_open_minutes' => 30,
            'crosses_midnight' => false,
        ]);
    }

    private function attendancePayload(
        string $eventType,
        string $deviceTime,
        string $photoVariant = ''
    ): array {
        return [
            'event_type' => $eventType,
            'photo' => $this->fakeSelfie($photoVariant),
            'latitude' => -0.6447000,
            'longitude' => 100.1401000,
            'location_accuracy' => 10,
            'device_time' => $deviceTime,
            'timezone' => 'Asia/Jakarta',
            'device_id' => 'integrity-device',
        ];
    }

    private function fakeSelfie(string $variant = ''): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'hadirin-integrity-');
        $image = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9ZQmcAAAAASUVORK5CYII=',
            true
        );
        file_put_contents($path, $image.$variant);

        return new UploadedFile($path, 'selfie.png', 'image/png', null, true);
    }

    private function makeReviewEvent(AttendanceRecord $record): AttendanceEvent
    {
        return AttendanceEvent::create([
            'attendance_record_id' => $record->id,
            'event_type' => 'check_in',
            'server_time' => $record->checked_in_at,
            'device_time' => $record->checked_in_at,
            'latitude' => -0.6447000,
            'longitude' => 100.1401000,
            'location_accuracy' => 10,
            'distance_meters' => 0,
            'inside_geofence' => true,
            'photo_path' => 'attendance/test.png',
            'photo_hash' => str_repeat('a', 64),
            'validation_status' => 'review',
            'rejection_reason' => 'Perlu diperiksa',
        ]);
    }
}
