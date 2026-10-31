<?php

namespace App\Services;

use App\Models\AttendanceEvent;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class AttendanceService
{
    public function __construct(
        private readonly GeofenceService $geofence,
        private readonly AuditService $audit,
    ) {}

    public function submit(Employee $employee, Request $request): AttendanceRecord
    {
        if (! $employee->attendance_enabled || ! $employee->user->is_active) {
            throw ValidationException::withMessages([
                'account' => 'Akun tidak diperbolehkan melakukan absensi.',
            ]);
        }

        $now = CarbonImmutable::now(config('app.timezone'));
        $eventType = $request->string('event_type')->toString();
        $schedule = $this->resolveSchedule($employee, $now, $eventType);

        if (! $schedule) {
            throw ValidationException::withMessages([
                'schedule' => 'Tidak ada jadwal kerja aktif untuk waktu ini.',
            ]);
        }

        $photo = $request->file('photo');
        $photoPath = null;

        try {
            return DB::transaction(function () use (
                $employee,
                $request,
                $schedule,
                $now,
                $eventType,
                $photo,
                &$photoPath
            ): AttendanceRecord {
                // Urutan lock ini juga dipakai approval izin dan penutupan hari.
                $lockedEmployee = Employee::query()
                    ->whereKey($employee->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();
                $lockedEmployee->load('user');

                if (! $lockedEmployee->attendance_enabled || ! $lockedEmployee->user->is_active) {
                    throw ValidationException::withMessages([
                        'account' => 'Akun tidak diperbolehkan melakukan absensi.',
                    ]);
                }

                $lockedSchedule = EmployeeSchedule::query()
                    ->whereKey($schedule->getKey())
                    ->lockForUpdate()
                    ->first();

                if (
                    ! $lockedSchedule
                    || $lockedSchedule->employee_id !== $lockedEmployee->id
                    || $lockedSchedule->status !== 'working'
                ) {
                    throw ValidationException::withMessages([
                        'schedule' => 'Jadwal kerja sudah berubah atau tidak lagi aktif.',
                    ]);
                }

                $lockedSchedule->load(['shift', 'workLocation']);
                $location = $lockedSchedule->workLocation;

                if (! $location || ! $location->is_active) {
                    throw ValidationException::withMessages([
                        'location' => 'Lokasi kerja pada jadwal sedang tidak aktif.',
                    ]);
                }

                $accuracy = (float) $request->input('location_accuracy');
                if ($accuracy > $location->maximum_accuracy_meters) {
                    throw ValidationException::withMessages([
                        'location_accuracy' => "Akurasi GPS ±{$accuracy} meter belum memenuhi batas maksimal ±{$location->maximum_accuracy_meters} meter.",
                    ]);
                }

                $distance = $this->geofence->distanceMeters(
                    (float) $request->input('latitude'),
                    (float) $request->input('longitude'),
                    (float) $location->latitude,
                    (float) $location->longitude,
                );

                if ($distance > $location->radius_meters) {
                    throw ValidationException::withMessages([
                        'location' => "Posisi Anda berjarak {$distance} meter dari {$location->name}. Radius maksimal {$location->radius_meters} meter.",
                    ]);
                }

                [$shiftStart, $shiftEnd] = $this->shiftWindow($lockedSchedule);
                if (config('attendance.enforce_time_window', true)) {
                    $this->validateEventWindow(
                        $eventType,
                        $lockedSchedule,
                        $now,
                        $shiftStart,
                        $shiftEnd
                    );
                }

                $record = AttendanceRecord::query()
                    ->where('employee_id', $lockedEmployee->id)
                    ->where('attendance_date', $lockedSchedule->work_date->toDateString())
                    ->lockForUpdate()
                    ->first();

                if ($record && $record->schedule_id && $record->schedule_id !== $lockedSchedule->id) {
                    throw ValidationException::withMessages([
                        'schedule' => 'Data absensi pada tanggal ini tidak sesuai dengan jadwal aktif.',
                    ]);
                }

                if (! $record) {
                    $record = AttendanceRecord::create([
                        'employee_id' => $lockedEmployee->id,
                        'schedule_id' => $lockedSchedule->id,
                        'attendance_date' => $lockedSchedule->work_date->toDateString(),
                        'status' => 'present',
                    ]);
                }

                if ($record->events()->where('event_type', $eventType)->exists()) {
                    throw ValidationException::withMessages([
                        'event_type' => $eventType === 'check_in'
                            ? 'Absensi masuk pada jadwal ini sudah tercatat.'
                            : 'Absensi pulang pada jadwal ini sudah tercatat.',
                    ]);
                }

                if ($eventType === 'check_out' && ! $record->checked_in_at) {
                    throw ValidationException::withMessages([
                        'event_type' => 'Lakukan absensi masuk terlebih dahulu.',
                    ]);
                }

                $extension = $photo->guessExtension() ?: 'jpg';
                $photoPath = $photo->storeAs(
                    "attendance/{$lockedEmployee->id}/{$now->format('Y/m')}",
                    "{$eventType}-{$now->format('YmdHis')}-".str()->uuid().".{$extension}",
                    'local'
                );

                $photoHash = hash_file('sha256', Storage::disk('local')->path($photoPath));
                $deviceTime = CarbonImmutable::parse($request->input('device_time'))->utc();

                $reviewReasons = [];
                $clockSkew = (int) round(abs(
                    $deviceTime->diffInMinutes($now->utc(), false)
                ));
                if ($clockSkew > config('attendance.maximum_device_clock_skew_minutes', 10)) {
                    $reviewReasons[] = "Selisih jam perangkat {$clockSkew} menit";
                }

                if (config('attendance.duplicate_photo_review', true)) {
                    $duplicateExists = AttendanceEvent::query()
                        ->where('photo_hash', $photoHash)
                        ->exists();
                    if ($duplicateExists) {
                        $reviewReasons[] = 'Hash foto identik ditemukan pada absensi sebelumnya';
                    }
                }

                $validationStatus = $reviewReasons === [] ? 'valid' : 'review';
                $lateThreshold = $shiftStart->addMinutes(
                    $lockedSchedule->shift->late_tolerance_minutes
                );
                $lateMinutes = $now->greaterThan($lateThreshold)
                    ? (int) round($shiftStart->diffInMinutes($now))
                    : 0;

                $event = AttendanceEvent::create([
                    'attendance_record_id' => $record->id,
                    'event_type' => $eventType,
                    'server_time' => $now,
                    'device_time' => $deviceTime,
                    'latitude' => $request->input('latitude'),
                    'longitude' => $request->input('longitude'),
                    'location_accuracy' => $request->input('location_accuracy'),
                    'distance_meters' => $distance,
                    'inside_geofence' => true,
                    'photo_path' => $photoPath,
                    'photo_hash' => $photoHash,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'validation_status' => $validationStatus,
                    'rejection_reason' => $reviewReasons === []
                        ? null
                        : implode('; ', $reviewReasons),
                    'metadata' => [
                        'device_id' => $request->input('device_id'),
                        'timezone' => $request->input('timezone'),
                    ],
                ]);

                if ($eventType === 'check_in') {
                    $record->forceFill([
                        'checked_in_at' => $now,
                        'status' => $validationStatus === 'review'
                            ? 'pending_review'
                            : ($lateMinutes > 0 ? 'late' : 'present'),
                        'late_minutes' => $lateMinutes,
                    ])->save();
                } else {
                    $earlyLeaveMinutes = $now->lessThan($shiftEnd)
                        ? (int) round($now->diffInMinutes($shiftEnd))
                        : 0;
                    $workDuration = $record->checked_in_at
                        ? (int) round($record->checked_in_at->diffInMinutes($now))
                        : 0;

                    $record->forceFill([
                        'checked_out_at' => $now,
                        'early_leave_minutes' => $earlyLeaveMinutes,
                        'work_duration_minutes' => $workDuration,
                        'status' => $validationStatus === 'review'
                            ? 'pending_review'
                            : $record->status,
                    ])->save();
                }

                $this->audit->record(
                    $request,
                    "attendance.{$eventType}",
                    $event,
                    null,
                    [
                        'distance_meters' => $distance,
                        'server_time' => $now->toIso8601String(),
                        'validation_status' => $validationStatus,
                    ]
                );

                return $record->fresh([
                    'events',
                    'schedule.shift',
                    'schedule.workLocation',
                ]);
            }, attempts: 3);
        } catch (Throwable $exception) {
            if ($photoPath && Storage::disk('local')->exists($photoPath)) {
                Storage::disk('local')->delete($photoPath);
            }

            throw $exception;
        }
    }

    public function resolveSchedule(
        Employee $employee,
        CarbonImmutable $now,
        ?string $eventType = null
    ): ?EmployeeSchedule {
        $yesterday = $this->scheduleOnDate($employee, $now->subDay());
        $today = $this->scheduleOnDate($employee, $now);

        if ($eventType === 'check_out') {
            $graceHours = config('attendance.checkout_grace_hours', 12);

            // Dahulukan jadwal dengan check-in yang belum memiliki check-out.
            foreach ([$yesterday, $today] as $candidate) {
                if (! $candidate) {
                    continue;
                }

                [, $shiftEnd] = $this->shiftWindow($candidate);
                $record = $candidate->attendanceRecord;
                if (
                    $now->lessThanOrEqualTo($shiftEnd->addHours($graceHours))
                    && $record?->checked_in_at
                    && ! $record->checked_out_at
                ) {
                    return $candidate;
                }
            }

            if ($yesterday) {
                [, $shiftEnd] = $this->shiftWindow($yesterday);
                if ($now->lessThanOrEqualTo($shiftEnd->addHours($graceHours))) {
                    return $yesterday;
                }
            }

            return $today;
        }

        // Check-in setelah tengah malam tetap menjadi bagian shift kemarin.
        if ($yesterday?->shift->crosses_midnight) {
            [, $shiftEnd] = $this->shiftWindow($yesterday);
            if ($now->lessThanOrEqualTo($shiftEnd)) {
                return $yesterday;
            }
        }

        return $today;
    }

    private function scheduleOnDate(
        Employee $employee,
        CarbonImmutable $date
    ): ?EmployeeSchedule {
        return EmployeeSchedule::query()
            ->with(['shift', 'workLocation', 'attendanceRecord'])
            ->where('employee_id', $employee->id)
            ->where('work_date', $date->toDateString())
            ->first();
    }

    private function shiftWindow(EmployeeSchedule $schedule): array
    {
        $workDate = $schedule->work_date->toDateString();
        $shiftStart = CarbonImmutable::parse(
            $workDate.' '.$schedule->shift->start_time,
            config('app.timezone')
        );
        $shiftEnd = CarbonImmutable::parse(
            $workDate.' '.$schedule->shift->end_time,
            config('app.timezone')
        );

        if ($schedule->shift->crosses_midnight) {
            $shiftEnd = $shiftEnd->addDay();
        }

        return [$shiftStart, $shiftEnd];
    }

    private function validateEventWindow(
        string $eventType,
        EmployeeSchedule $schedule,
        CarbonImmutable $now,
        CarbonImmutable $shiftStart,
        CarbonImmutable $shiftEnd
    ): void {
        if ($eventType === 'check_in') {
            $openAt = $shiftStart->subMinutes($schedule->shift->check_in_open_minutes);
            if ($now->lessThan($openAt)) {
                throw ValidationException::withMessages([
                    'event_type' => 'Absensi masuk belum dibuka. Silakan mencoba pada '.$openAt->format('H:i').'.',
                ]);
            }
            if ($now->greaterThan($shiftEnd)) {
                throw ValidationException::withMessages([
                    'event_type' => 'Waktu absensi masuk untuk jadwal ini sudah berakhir.',
                ]);
            }

            return;
        }

        $openAt = $shiftEnd->subMinutes($schedule->shift->check_out_open_minutes);
        if ($now->lessThan($openAt)) {
            throw ValidationException::withMessages([
                'event_type' => 'Absensi pulang baru dibuka pada '.$openAt->format('H:i').'.',
            ]);
        }

        $graceHours = config('attendance.checkout_grace_hours', 12);
        if ($now->greaterThan($shiftEnd->addHours($graceHours))) {
            throw ValidationException::withMessages([
                'event_type' => 'Batas absensi pulang untuk jadwal ini sudah berakhir.',
            ]);
        }
    }
}
