<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceSubmissionRequest;
use App\Models\AttendanceRecord;
use App\Services\AttendanceService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function capture(Request $request, AttendanceService $attendanceService): View
    {
        $type = $request->query('type', 'check_in');
        abort_unless(in_array($type, ['check_in', 'check_out'], true), 404);

        $employee = $request->user()->employee()->with('user')->firstOrFail();
        $schedule = $attendanceService->resolveSchedule(
            $employee,
            CarbonImmutable::now(config('app.timezone')),
            $type
        );

        $record = $schedule
            ? AttendanceRecord::with('events')
                ->where('employee_id', $employee->id)
                ->whereDate('attendance_date', $schedule->work_date->toDateString())
                ->first()
            : null;

        if ($type === 'check_in' && $record?->checked_in_at) {
            return view('employee.attendance.capture', compact('type', 'schedule', 'record'))
                ->with('pageError', 'Absensi masuk pada jadwal ini sudah tercatat.');
        }
        if ($type === 'check_out' && ! $record?->checked_in_at) {
            return view('employee.attendance.capture', compact('type', 'schedule', 'record'))
                ->with('pageError', 'Lakukan absensi masuk terlebih dahulu.');
        }
        if ($type === 'check_out' && $record?->checked_out_at) {
            return view('employee.attendance.capture', compact('type', 'schedule', 'record'))
                ->with('pageError', 'Absensi pulang pada jadwal ini sudah tercatat.');
        }

        return view('employee.attendance.capture', compact('type', 'schedule', 'record'));
    }

    public function store(
        AttendanceSubmissionRequest $request,
        AttendanceService $attendanceService
    ): JsonResponse {
        $employee = $request->user()->employee()->with('user')->firstOrFail();
        $record = $attendanceService->submit($employee, $request);

        return response()->json([
            'message' => $request->input('event_type') === 'check_in'
                ? 'Absensi masuk berhasil dicatat.'
                : 'Absensi pulang berhasil dicatat.',
            'redirect' => route('employee.dashboard'),
            'attendance' => [
                'status' => $record->status,
                'checked_in_at' => $record->checked_in_at?->format('H:i'),
                'checked_out_at' => $record->checked_out_at?->format('H:i'),
                'late_minutes' => $record->late_minutes,
            ],
        ]);
    }

    public function history(Request $request): View
    {
        $employee = $request->user()->employee()->firstOrFail();
        $month = $request->string('month', now(config('app.timezone'))->format('Y-m'))->toString();
        abort_unless(preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month) === 1, 422, 'Format bulan tidak valid.');
        [$year, $monthNumber] = array_map('intval', explode('-', $month));

        $records = AttendanceRecord::query()
            ->with(['events', 'schedule.shift', 'schedule.workLocation'])
            ->where('employee_id', $employee->id)
            ->whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $monthNumber)
            ->latest('attendance_date')
            ->paginate(15)
            ->withQueryString();

        $summary = AttendanceRecord::query()
            ->where('employee_id', $employee->id)
            ->whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $monthNumber)
            ->selectRaw("COUNT(*) total, SUM(CASE WHEN status IN ('present','late') THEN 1 ELSE 0 END) present, SUM(CASE WHEN status='late' THEN 1 ELSE 0 END) late, COALESCE(SUM(late_minutes),0) late_minutes")
            ->first();

        return view('employee.attendance.history', compact('records', 'summary', 'month'));
    }
}
