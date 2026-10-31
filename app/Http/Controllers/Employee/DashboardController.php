<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\LeaveRequest;
use App\Services\AttendanceService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, AttendanceService $attendanceService): View
    {
        $employee = $request->user()->employee()->with([
            'department', 'position', 'defaultLocation',
        ])->firstOrFail();

        $now = CarbonImmutable::now(config('app.timezone'));
        $tomorrow = $now->addDay()->toDateString();
        $checkoutSchedule = $attendanceService->resolveSchedule($employee, $now, 'check_out');
        $checkoutAttendance = $checkoutSchedule
            ? AttendanceRecord::with('events')
                ->where('employee_id', $employee->id)
                ->where('attendance_date', '>=', $checkoutSchedule->work_date->toDateString())
                ->where('attendance_date', '<', $checkoutSchedule->work_date->copy()->addDay()->toDateString())
                ->first()
            : null;

        $hasPendingCheckout = $checkoutAttendance?->checked_in_at
            && ! $checkoutAttendance->checked_out_at;

        $schedule = $hasPendingCheckout
            ? $checkoutSchedule
            : $attendanceService->resolveSchedule($employee, $now);

        $attendance = $hasPendingCheckout
            ? $checkoutAttendance
            : ($schedule
                ? AttendanceRecord::with('events')
                    ->where('employee_id', $employee->id)
                    ->where('attendance_date', '>=', $schedule->work_date->toDateString())
                    ->where('attendance_date', '<', $schedule->work_date->copy()->addDay()->toDateString())
                    ->first()
                : null);

        $monthly = AttendanceRecord::query()
            ->where('employee_id', $employee->id)
            ->where('attendance_date', '>=', $now->startOfMonth()->toDateString())
            ->where('attendance_date', '<', $tomorrow)
            ->selectRaw("COUNT(*) AS total, SUM(CASE WHEN status IN ('present','late') THEN 1 ELSE 0 END) AS present_count, SUM(CASE WHEN status='late' THEN 1 ELSE 0 END) AS late_count")
            ->first();

        $pendingLeave = LeaveRequest::where('employee_id', $employee->id)
            ->where('status', 'pending')
            ->count();

        $recent = AttendanceRecord::query()
            ->where('employee_id', $employee->id)
            ->where('attendance_date', '<', $tomorrow)
            ->latest('attendance_date')
            ->limit(5)
            ->get();

        return view('employee.dashboard', compact(
            'employee', 'schedule', 'attendance', 'monthly', 'pendingLeave', 'recent'
        ));
    }
}
