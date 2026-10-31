<?php

namespace App\Providers;

use App\Models\AttendanceRecord;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $timezone = config('attendance.timezone', 'Asia/Jakarta');
        config(['app.timezone' => $timezone]);
        date_default_timezone_set($timezone);
        Carbon::setLocale(config('app.locale', 'id'));
        Paginator::useTailwind();

        View::composer('layouts.employee', function ($view): void {
            $user = auth()->user();
            $attendanceNavigationType = 'check_in';

            if ($user?->hasRole('employee')) {
                $employee = $user->employee()->first();

                if ($employee) {
                    $now = CarbonImmutable::now(config('app.timezone'));
                    $schedule = app(AttendanceService::class)->resolveSchedule(
                        $employee,
                        $now,
                        'check_out'
                    );

                    $record = $schedule
                        ? AttendanceRecord::query()
                            ->where('employee_id', $employee->id)
                            ->whereDate('attendance_date', $schedule->work_date->toDateString())
                            ->first(['checked_in_at', 'checked_out_at'])
                        : null;

                    if ($record?->checked_in_at && ! $record->checked_out_at) {
                        $attendanceNavigationType = 'check_out';
                    }
                }
            }

            $view->with([
                'attendanceNavigationType' => $attendanceNavigationType,
                'attendanceNavigationLabel' => $attendanceNavigationType === 'check_out'
                    ? 'Pulang'
                    : 'Absen',
            ]);
        });
    }
}
