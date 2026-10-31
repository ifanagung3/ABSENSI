<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\LeaveRequest;
use App\Models\Shift;
use App\Models\User;
use App\Models\WorkLocation;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class LeaveRequestFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_store_leave_with_an_attachment_and_atomic_audit(): void
    {
        Storage::fake('local');
        [$user, $employee] = $this->makeEmployee('store');

        $this->actingAs($user)
            ->from(route('employee.leave.index'))
            ->post(route('employee.leave.store'), [
                'request_type' => 'sick',
                'start_date' => '2099-01-10',
                'end_date' => '2099-01-11',
                'reason' => 'Perlu beristirahat berdasarkan anjuran dokter.',
                'attachment' => $this->fakePngUpload('surat-dokter.png'),
            ])
            ->assertRedirect(route('employee.leave.index'))
            ->assertSessionHas('success');

        $leave = LeaveRequest::query()->sole();

        $this->assertSame($employee->id, $leave->employee_id);
        $this->assertSame('pending', $leave->status);
        $this->assertNotNull($leave->attachment_path);
        Storage::disk('local')->assertExists($leave->attachment_path);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'leave.created',
            'entity_id' => $leave->id,
        ]);
    }

    public function test_employee_can_request_at_most_366_inclusive_days(): void
    {
        [$user] = $this->makeEmployee('max-span');

        $this->actingAs($user)
            ->from(route('employee.leave.index'))
            ->post(route('employee.leave.store'), [
                'request_type' => 'leave',
                'start_date' => '2099-01-01',
                'end_date' => '2100-01-01',
                'reason' => 'Pengajuan cuti panjang untuk kebutuhan keluarga.',
            ])
            ->assertRedirect(route('employee.leave.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('leave_requests', 1);
    }

    public function test_employee_cannot_request_more_than_366_inclusive_days(): void
    {
        [$user] = $this->makeEmployee('over-span');

        $this->actingAs($user)
            ->from(route('employee.leave.index'))
            ->post(route('employee.leave.store'), [
                'request_type' => 'leave',
                'start_date' => '2099-01-01',
                'end_date' => '2100-01-02',
                'reason' => 'Pengajuan cuti panjang untuk kebutuhan keluarga.',
            ])
            ->assertRedirect(route('employee.leave.index'))
            ->assertSessionHasErrors('end_date');

        $this->assertDatabaseCount('leave_requests', 0);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    #[DataProvider('activeLeaveStatuses')]
    public function test_employee_cannot_store_an_overlapping_active_request(string $status): void
    {
        [$user, $employee] = $this->makeEmployee('overlap-'.$status);
        $this->makeLeave($employee, [
            'start_date' => '2099-02-10',
            'end_date' => '2099-02-12',
            'status' => $status,
        ]);

        $this->actingAs($user)
            ->from(route('employee.leave.index'))
            ->post(route('employee.leave.store'), [
                'request_type' => 'permission',
                'start_date' => '2099-02-12',
                'end_date' => '2099-02-14',
                'reason' => 'Mengurus keperluan keluarga di luar kota.',
            ])
            ->assertRedirect(route('employee.leave.index'))
            ->assertSessionHasErrors('start_date');

        $this->assertDatabaseCount('leave_requests', 1);
    }

    public static function activeLeaveStatuses(): array
    {
        return [
            'pending' => ['pending'],
            'approved' => ['approved'],
        ];
    }

    public function test_rejected_or_cancelled_request_does_not_block_a_new_request(): void
    {
        [$user, $employee] = $this->makeEmployee('inactive-overlap');

        foreach (['rejected', 'cancelled'] as $index => $status) {
            $this->makeLeave($employee, [
                'start_date' => '2099-03-'.str_pad((string) ($index + 10), 2, '0', STR_PAD_LEFT),
                'end_date' => '2099-03-'.str_pad((string) ($index + 10), 2, '0', STR_PAD_LEFT),
                'status' => $status,
            ]);
        }

        $this->actingAs($user)
            ->from(route('employee.leave.index'))
            ->post(route('employee.leave.store'), [
                'request_type' => 'permission',
                'start_date' => '2099-03-10',
                'end_date' => '2099-03-11',
                'reason' => 'Mengurus keperluan keluarga di luar kota.',
            ])
            ->assertRedirect(route('employee.leave.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('leave_requests', 3);
    }

    public function test_failed_store_removes_orphan_attachment_and_rolls_back_leave(): void
    {
        Storage::fake('local');
        [$user, $employee] = $this->makeEmployee('failed-store');

        $this->mock(AuditService::class, function ($mock): void {
            $mock->shouldReceive('record')
                ->once()
                ->andThrow(new RuntimeException('Simulated audit failure.'));
        });

        try {
            $this->withoutExceptionHandling()
                ->actingAs($user)
                ->post(route('employee.leave.store'), [
                    'request_type' => 'sick',
                    'start_date' => '2099-04-01',
                    'end_date' => '2099-04-01',
                    'reason' => 'Perlu beristirahat berdasarkan anjuran dokter.',
                    'attachment' => $this->fakePngUpload('bukti.png'),
                ]);

            $this->fail('Audit failure seharusnya diteruskan oleh controller.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated audit failure.', $exception->getMessage());
        }

        $this->assertDatabaseCount('leave_requests', 0);
        $this->assertDatabaseCount('audit_logs', 0);
        $this->assertSame([], Storage::disk('local')->allFiles("leave-attachments/{$employee->id}"));
    }

    public function test_employee_can_cancel_only_their_pending_request_without_changing_schedule_or_record(): void
    {
        [$user, $employee] = $this->makeEmployee('cancel');
        $schedule = $this->makeSchedule($employee, '2099-05-01');
        $record = AttendanceRecord::create([
            'employee_id' => $employee->id,
            'schedule_id' => $schedule->id,
            'attendance_date' => '2099-05-01',
            'status' => 'absent',
        ]);
        $leave = $this->makeLeave($employee, [
            'start_date' => '2099-05-01',
            'end_date' => '2099-05-01',
        ]);

        $this->actingAs($user)
            ->from(route('employee.leave.index'))
            ->patch(route('employee.leave.cancel', $leave))
            ->assertRedirect(route('employee.leave.index'))
            ->assertSessionHas('success');

        $this->assertSame('cancelled', $leave->fresh()->status);
        $this->assertSame('working', $schedule->fresh()->status);
        $this->assertSame('absent', $record->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'leave.cancelled',
            'entity_id' => $leave->id,
        ]);
    }

    public function test_employee_cannot_cancel_another_employees_request(): void
    {
        [$owner, $ownerEmployee] = $this->makeEmployee('cancel-owner');
        [$other] = $this->makeEmployee('cancel-other');
        $leave = $this->makeLeave($ownerEmployee);

        $this->actingAs($other)
            ->patch(route('employee.leave.cancel', $leave))
            ->assertForbidden();

        $this->assertSame('pending', $leave->fresh()->status);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_employee_cannot_cancel_a_request_that_is_no_longer_pending(): void
    {
        [$user, $employee] = $this->makeEmployee('cancel-reviewed');
        $leave = $this->makeLeave($employee, ['status' => 'approved']);

        $this->actingAs($user)
            ->patch(route('employee.leave.cancel', $leave))
            ->assertUnprocessable();

        $this->assertSame('approved', $leave->fresh()->status);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_failed_cancel_audit_rolls_back_the_status_change(): void
    {
        [$user, $employee] = $this->makeEmployee('failed-cancel');
        $leave = $this->makeLeave($employee);

        $this->mock(AuditService::class, function ($mock): void {
            $mock->shouldReceive('record')
                ->once()
                ->andThrow(new RuntimeException('Simulated audit failure.'));
        });

        try {
            $this->withoutExceptionHandling()
                ->actingAs($user)
                ->patch(route('employee.leave.cancel', $leave));

            $this->fail('Audit failure seharusnya diteruskan oleh controller.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated audit failure.', $exception->getMessage());
        }

        $this->assertSame('pending', $leave->fresh()->status);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    #[DataProvider('leaveTypeStatuses')]
    public function test_admin_approval_updates_only_working_schedules_and_matching_records(
        string $requestType,
        string $expectedRecordStatus
    ): void {
        $admin = $this->makeAdmin('approve-'.$requestType);
        [, $employee] = $this->makeEmployee('approve-'.$requestType);
        $workingOne = $this->makeSchedule($employee, '2099-06-01');
        $off = $this->makeSchedule($employee, '2099-06-02', 'off');
        $workingTwo = $this->makeSchedule($employee, '2099-06-03');
        AttendanceRecord::create([
            'employee_id' => $employee->id,
            'schedule_id' => $workingOne->id,
            'attendance_date' => '2099-06-01',
            'status' => 'absent',
        ]);
        $leave = $this->makeLeave($employee, [
            'request_type' => $requestType,
            'start_date' => '2099-06-01',
            'end_date' => '2099-06-03',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.leave.index'))
            ->patch(route('admin.leave.review', $leave), [
                'status' => 'approved',
                'review_notes' => 'Dokumen dan rentang tanggal sudah diperiksa.',
            ])
            ->assertRedirect(route('admin.leave.index'))
            ->assertSessionHas('success');

        $reviewedLeave = $leave->fresh();
        $this->assertSame('approved', $reviewedLeave->status);
        $this->assertSame($admin->id, $reviewedLeave->reviewed_by);
        $this->assertNotNull($reviewedLeave->reviewed_at);
        $this->assertSame('leave', $workingOne->fresh()->status);
        $this->assertSame('off', $off->fresh()->status);
        $this->assertSame('leave', $workingTwo->fresh()->status);

        foreach ([$workingOne, $workingTwo] as $schedule) {
            $record = AttendanceRecord::query()
                ->where('employee_id', $employee->id)
                ->whereDate('attendance_date', $schedule->work_date->toDateString())
                ->sole();

            $this->assertSame($schedule->id, $record->schedule_id);
            $this->assertSame($expectedRecordStatus, $record->status);
            $this->assertSame('Dibuat dari pengajuan #'.$leave->id, $record->notes);
        }

        $this->assertDatabaseMissing('attendance_records', [
            'employee_id' => $employee->id,
            'attendance_date' => '2099-06-02',
        ]);
        $this->assertDatabaseCount('attendance_records', 2);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'leave.reviewed',
            'entity_id' => $leave->id,
        ]);
    }

    public static function leaveTypeStatuses(): array
    {
        return [
            'permission' => ['permission', 'permission'],
            'sick' => ['sick', 'sick'],
            'leave' => ['leave', 'leave'],
        ];
    }

    public function test_admin_cannot_approve_when_employee_has_checked_in_and_everything_rolls_back(): void
    {
        $admin = $this->makeAdmin('attendance-conflict');
        [, $employee] = $this->makeEmployee('attendance-conflict');
        $schedule = $this->makeSchedule($employee, '2099-07-01');
        AttendanceRecord::create([
            'employee_id' => $employee->id,
            'schedule_id' => $schedule->id,
            'attendance_date' => '2099-07-01',
            'status' => 'present',
            'checked_in_at' => '2099-07-01 08:00:00',
        ]);
        $leave = $this->makeLeave($employee, [
            'start_date' => '2099-07-01',
            'end_date' => '2099-07-01',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.leave.index'))
            ->patch(route('admin.leave.review', $leave), ['status' => 'approved'])
            ->assertRedirect(route('admin.leave.index'))
            ->assertSessionHasErrors('status');

        $this->assertSame('pending', $leave->fresh()->status);
        $this->assertSame('working', $schedule->fresh()->status);
        $this->assertSame('present', AttendanceRecord::query()->sole()->status);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_admin_rejection_does_not_change_schedules_or_attendance_records(): void
    {
        $admin = $this->makeAdmin('reject');
        [, $employee] = $this->makeEmployee('reject');
        $schedule = $this->makeSchedule($employee, '2099-08-01');
        $record = AttendanceRecord::create([
            'employee_id' => $employee->id,
            'schedule_id' => $schedule->id,
            'attendance_date' => '2099-08-01',
            'status' => 'absent',
        ]);
        $leave = $this->makeLeave($employee, [
            'start_date' => '2099-08-01',
            'end_date' => '2099-08-01',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.leave.index'))
            ->patch(route('admin.leave.review', $leave), [
                'status' => 'rejected',
                'review_notes' => 'Dokumen pendukung belum mencukupi.',
            ])
            ->assertRedirect(route('admin.leave.index'))
            ->assertSessionHas('success');

        $this->assertSame('rejected', $leave->fresh()->status);
        $this->assertSame('working', $schedule->fresh()->status);
        $this->assertSame('absent', $record->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'leave.reviewed',
            'entity_id' => $leave->id,
        ]);
    }

    public function test_admin_cannot_review_a_request_that_is_no_longer_pending(): void
    {
        $admin = $this->makeAdmin('reviewed');
        [, $employee] = $this->makeEmployee('reviewed');
        $schedule = $this->makeSchedule($employee, '2099-08-10');
        $leave = $this->makeLeave($employee, [
            'start_date' => '2099-08-10',
            'end_date' => '2099-08-10',
            'status' => 'approved',
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.leave.review', $leave), ['status' => 'rejected'])
            ->assertUnprocessable();

        $this->assertSame('approved', $leave->fresh()->status);
        $this->assertSame('working', $schedule->fresh()->status);
        $this->assertDatabaseCount('attendance_records', 0);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_failed_approval_audit_rolls_back_leave_schedule_and_record_changes(): void
    {
        $admin = $this->makeAdmin('failed-approval');
        [, $employee] = $this->makeEmployee('failed-approval');
        $schedule = $this->makeSchedule($employee, '2099-09-01');
        $leave = $this->makeLeave($employee, [
            'start_date' => '2099-09-01',
            'end_date' => '2099-09-01',
        ]);

        $this->mock(AuditService::class, function ($mock): void {
            $mock->shouldReceive('record')
                ->once()
                ->andThrow(new RuntimeException('Simulated audit failure.'));
        });

        try {
            $this->withoutExceptionHandling()
                ->actingAs($admin)
                ->patch(route('admin.leave.review', $leave), ['status' => 'approved']);

            $this->fail('Audit failure seharusnya diteruskan oleh controller.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated audit failure.', $exception->getMessage());
        }

        $this->assertSame('pending', $leave->fresh()->status);
        $this->assertSame('working', $schedule->fresh()->status);
        $this->assertDatabaseCount('attendance_records', 0);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_employee_can_open_only_their_own_attachment_while_admin_can_open_any_attachment(): void
    {
        Storage::fake('local');
        [$owner, $ownerEmployee] = $this->makeEmployee('attachment-owner');
        [$other] = $this->makeEmployee('attachment-other');
        $admin = $this->makeAdmin('attachment-admin');
        $path = "leave-attachments/{$ownerEmployee->id}/evidence.png";
        Storage::disk('local')->put($path, $this->pngContents());
        $leave = $this->makeLeave($ownerEmployee, ['attachment_path' => $path]);

        $this->actingAs($owner)
            ->get(route('employee.leave.attachment', $leave))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->actingAs($other)
            ->get(route('employee.leave.attachment', $leave))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('admin.leave.attachment', $leave))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }

    private function makeAdmin(string $suffix): User
    {
        return User::factory()->create([
            'name' => 'Admin '.$suffix,
            'email' => "admin-{$suffix}@example.test",
            'role' => 'admin',
            'is_active' => true,
            'must_change_password' => false,
        ]);
    }

    private function makeEmployee(string $suffix): array
    {
        $user = User::factory()->create([
            'name' => 'Pegawai '.$suffix,
            'email' => "employee-{$suffix}@example.test",
            'role' => 'employee',
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $employee = Employee::create([
            'user_id' => $user->id,
            'employee_number' => 'EMP-'.strtoupper($suffix),
            'employment_status' => 'permanent',
            'attendance_enabled' => true,
        ]);

        return [$user, $employee];
    }

    private function makeSchedule(Employee $employee, string $date, string $status = 'working'): EmployeeSchedule
    {
        $location = WorkLocation::firstOrCreate(
            ['name' => 'Lokasi Test'],
            [
                'address' => 'Alamat lokasi test',
                'latitude' => -0.6447000,
                'longitude' => 100.1401000,
                'radius_meters' => 100,
                'maximum_accuracy_meters' => 100,
                'is_active' => true,
            ]
        );
        $shift = Shift::firstOrCreate(
            ['name' => 'Shift Test'],
            [
                'start_time' => '07:30',
                'end_time' => '15:30',
                'check_in_open_minutes' => 90,
                'late_tolerance_minutes' => 10,
                'check_out_open_minutes' => 30,
                'crosses_midnight' => false,
            ]
        );

        return EmployeeSchedule::create([
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'work_location_id' => $location->id,
            'work_date' => $date,
            'status' => $status,
        ]);
    }

    private function makeLeave(Employee $employee, array $attributes = []): LeaveRequest
    {
        return LeaveRequest::create([
            'employee_id' => $employee->id,
            'request_type' => 'permission',
            'start_date' => '2099-01-01',
            'end_date' => '2099-01-01',
            'reason' => 'Keperluan pribadi yang tidak dapat ditinggalkan.',
            'status' => 'pending',
            ...$attributes,
        ]);
    }

    private function fakePngUpload(string $name): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'hadirin-leave-');
        file_put_contents($path, $this->pngContents());

        return new UploadedFile($path, $name, 'image/png', null, true);
    }

    private function pngContents(): string
    {
        return base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9ZQmcAAAAASUVORK5CYII=',
            true
        );
    }
}
