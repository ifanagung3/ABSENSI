<?php

use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('attendance:close-day {date?}', function (?string $date = null): int {
    $timezone = config('attendance.timezone', 'Asia/Jakarta');
    $today = CarbonImmutable::today($timezone);

    if ($date !== null) {
        $parts = explode('-', $date);
        $validDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1
            && count($parts) === 3
            && checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0]);

        if (! $validDate) {
            $this->error('Tanggal harus menggunakan format YYYY-MM-DD yang valid.');

            return 1;
        }
    }

    $targetDate = $date ?? $today->subDay()->toDateString();

    if ($targetDate >= $today->toDateString()) {
        $this->error('Penutupan hanya dapat dijalankan untuk tanggal yang sudah berlalu.');

        return 1;
    }

    $nextDate = CarbonImmutable::parse($targetDate, $timezone)->addDay()->toDateString();

    $created = 0;
    EmployeeSchedule::query()
        ->where('work_date', '>=', $targetDate)
        ->where('work_date', '<', $nextDate)
        ->where('status', 'working')
        ->chunkById(200, function ($schedules) use (&$created): void {
            foreach ($schedules as $schedule) {
                DB::transaction(function () use ($schedule, &$created): void {
                    // Samakan urutan lock dengan check-in dan approval izin.
                    $employee = Employee::query()
                        ->whereKey($schedule->employee_id)
                        ->lockForUpdate()
                        ->first();
                    if (! $employee) {
                        return;
                    }

                    $lockedSchedule = EmployeeSchedule::query()
                        ->whereKey($schedule->getKey())
                        ->lockForUpdate()
                        ->first();
                    if (! $lockedSchedule || $lockedSchedule->status !== 'working') {
                        return;
                    }

                    $record = AttendanceRecord::query()
                        ->where('employee_id', $employee->id)
                        ->where('attendance_date', '>=', $lockedSchedule->work_date->toDateString())
                        ->where('attendance_date', '<', $lockedSchedule->work_date->copy()->addDay()->toDateString())
                        ->lockForUpdate()
                        ->first();
                    if ($record) {
                        return;
                    }

                    $record = AttendanceRecord::create([
                        'employee_id' => $employee->id,
                        'schedule_id' => $lockedSchedule->id,
                        'attendance_date' => $lockedSchedule->work_date->toDateString(),
                        'status' => 'absent',
                        'notes' => 'Dibuat otomatis saat penutupan hari.',
                    ]);
                    AuditLog::create([
                        'action' => 'attendance.auto_absent',
                        'entity_type' => AttendanceRecord::class,
                        'entity_id' => $record->id,
                        'new_values' => [
                            'employee_id' => $record->employee_id,
                            'schedule_id' => $record->schedule_id,
                            'attendance_date' => $record->attendance_date->toDateString(),
                            'status' => $record->status,
                        ],
                    ]);
                    $created++;
                }, attempts: 3);
            }
        });

    $this->info("Penutupan {$targetDate} selesai. {$created} data tidak hadir dibuat.");

    return 0;
})->purpose('Create absent records for working schedules without attendance');

Schedule::command('attendance:close-day')
    ->dailyAt('06:00')
    ->timezone(config('attendance.timezone', 'Asia/Jakarta'))
    ->withoutOverlapping();
