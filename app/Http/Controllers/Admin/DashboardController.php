<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\EmployeeSchedule;
use App\Models\LeaveRequest;
use App\Models\WorkLocation;
use Carbon\CarbonImmutable;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $now = CarbonImmutable::now(config('app.timezone'));
        $today = $now->toDateString();
        $tomorrow = $now->addDay()->toDateString();

        $scheduleCounts = EmployeeSchedule::query()
            ->where('work_date', '>=', $today)
            ->where('work_date', '<', $tomorrow)
            ->selectRaw("SUM(CASE WHEN status = 'working' THEN 1 ELSE 0 END) AS working_count")
            ->selectRaw("SUM(CASE WHEN status = 'leave' THEN 1 ELSE 0 END) AS leave_count")
            ->first();
        $workingScheduled = (int) ($scheduleCounts->working_count ?? 0);
        $excusedScheduled = (int) ($scheduleCounts->leave_count ?? 0);

        $attendanceCounts = AttendanceRecord::query()
            ->where('attendance_date', '>=', $today)
            ->where('attendance_date', '<', $tomorrow)
            ->selectRaw("SUM(CASE WHEN status IN ('present','late') THEN 1 ELSE 0 END) AS present_count")
            ->selectRaw("SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) AS late_count")
            ->selectRaw("SUM(CASE WHEN status IN ('permission','sick','leave') THEN 1 ELSE 0 END) AS leave_count")
            ->selectRaw("SUM(CASE WHEN status = 'pending_review' THEN 1 ELSE 0 END) AS pending_count")
            ->first();

        $stats = [
            'scheduled' => $workingScheduled + $excusedScheduled,
            'present' => (int) ($attendanceCounts->present_count ?? 0),
            'late' => (int) ($attendanceCounts->late_count ?? 0),
            'leave' => (int) ($attendanceCounts->leave_count ?? 0),
            'pending' => (int) ($attendanceCounts->pending_count ?? 0),
            'pending_leave' => LeaveRequest::where('status', 'pending')->count(),
        ];
        $stats['absent'] = max(0, $workingScheduled - $stats['present'] - $stats['pending']);

        $recent = AttendanceRecord::with([
            'employee.user', 'employee.department', 'schedule.workLocation', 'events',
        ])->where('attendance_date', '>=', $today)
            ->where('attendance_date', '<', $tomorrow)
            ->latest('checked_in_at')
            ->limit(10)
            ->get();

        $primaryLocation = WorkLocation::query()->where('is_active', true)->orderBy('id')->first();

        $weekly = AttendanceRecord::query()
            ->where('attendance_date', '>=', $now->subDays(6)->toDateString())
            ->where('attendance_date', '<', $tomorrow)
            ->selectRaw("DATE(attendance_date) AS attendance_date, SUM(CASE WHEN status IN ('present','late') THEN 1 ELSE 0 END) AS total")
            ->groupByRaw('DATE(attendance_date)')
            ->orderByRaw('DATE(attendance_date)')
            ->get();

        return view('admin.dashboard', compact('stats', 'recent', 'weekly', 'primaryLocation'));
    }
}
