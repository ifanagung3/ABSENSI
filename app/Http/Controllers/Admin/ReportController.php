<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        [$year, $month] = $this->resolveMonth($request);
        [$monthStart, $monthEnd] = $this->monthRange($year, $month);

        $summary = Employee::query()
            ->with(['user', 'department'])
            ->withCount([
                'schedules as scheduled_count' => fn ($query) => $query
                    ->where('work_date', '>=', $monthStart)->where('work_date', '<', $monthEnd)
                    ->whereIn('status', ['working', 'leave']),
                'attendanceRecords as present_count' => fn ($query) => $query
                    ->where('attendance_date', '>=', $monthStart)->where('attendance_date', '<', $monthEnd)
                    ->whereIn('status', ['present', 'late']),
                'attendanceRecords as late_count' => fn ($query) => $query
                    ->where('attendance_date', '>=', $monthStart)->where('attendance_date', '<', $monthEnd)
                    ->where('status', 'late'),
                'attendanceRecords as permission_count' => fn ($query) => $query
                    ->where('attendance_date', '>=', $monthStart)->where('attendance_date', '<', $monthEnd)
                    ->where('status', 'permission'),
                'attendanceRecords as sick_count' => fn ($query) => $query
                    ->where('attendance_date', '>=', $monthStart)->where('attendance_date', '<', $monthEnd)
                    ->where('status', 'sick'),
                'attendanceRecords as leave_count' => fn ($query) => $query
                    ->where('attendance_date', '>=', $monthStart)->where('attendance_date', '<', $monthEnd)
                    ->where('status', 'leave'),
                'attendanceRecords as absent_count' => fn ($query) => $query
                    ->where('attendance_date', '>=', $monthStart)->where('attendance_date', '<', $monthEnd)
                    ->whereIn('status', ['absent', 'invalid']),
            ])
            ->orderBy('employee_number')
            ->paginate(20)
            ->withQueryString();

        $totals = AttendanceRecord::query()
            ->where('attendance_date', '>=', $monthStart)
            ->where('attendance_date', '<', $monthEnd)
            ->selectRaw("COUNT(*) total, SUM(CASE WHEN status IN ('present','late') THEN 1 ELSE 0 END) present, SUM(CASE WHEN status='late' THEN 1 ELSE 0 END) late, SUM(CASE WHEN status IN ('permission','sick','leave') THEN 1 ELSE 0 END) excused, SUM(CASE WHEN status IN ('absent','invalid') THEN 1 ELSE 0 END) absent, COALESCE(SUM(late_minutes),0) late_minutes")
            ->first();

        return view('admin.reports.index', [
            'summary' => $summary,
            'totals' => $totals,
            'selectedMonth' => sprintf('%04d-%02d', $year, $month),
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        [$year, $month] = $this->resolveMonth($request);
        [$monthStart, $monthEnd] = $this->monthRange($year, $month);
        $monthLabel = sprintf('%04d-%02d', $year, $month);
        $filename = "laporan-absensi-{$monthLabel}.csv";

        return response()->streamDownload(function () use ($monthEnd, $monthStart): void {
            $handle = fopen('php://output', 'wb');
            if ($handle === false) {
                throw new \RuntimeException('File laporan tidak dapat dibuat.');
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'Tanggal', 'NIP', 'Nama', 'Unit Kerja', 'Jam Masuk', 'Jam Pulang',
                'Status', 'Terlambat (menit)', 'Pulang Lebih Awal (menit)', 'Durasi Kerja (menit)',
            ]);

            $maximumRecordId = AttendanceRecord::query()
                ->where('attendance_date', '>=', $monthStart)
                ->where('attendance_date', '<', $monthEnd)
                ->max('id');

            if ($maximumRecordId !== null) {
                AttendanceRecord::with(['employee.user', 'employee.department'])
                    ->where('attendance_date', '>=', $monthStart)
                    ->where('attendance_date', '<', $monthEnd)
                    ->where('id', '<=', $maximumRecordId)
                    ->chunkById(500, function ($records) use ($handle): void {
                        foreach ($records as $record) {
                            $isNonAttendanceStatus = in_array(
                                $record->status,
                                ['permission', 'sick', 'leave', 'absent'],
                                true
                            );

                            $row = [
                                $record->attendance_date?->format('d-m-Y') ?? '-',
                                $record->employee?->employee_number ?? '-',
                                $record->employee?->user?->name ?? '-',
                                $record->employee?->department?->name ?? '-',
                                $record->checked_in_at?->format('H:i:s') ?? '-',
                                $record->checked_out_at?->format('H:i:s') ?? '-',
                                $this->attendanceStatusLabel($record->status),
                                $isNonAttendanceStatus ? '-' : (int) ($record->late_minutes ?? 0),
                                $isNonAttendanceStatus ? '-' : (int) ($record->early_leave_minutes ?? 0),
                                $isNonAttendanceStatus ? '-' : (int) ($record->work_duration_minutes ?? 0),
                            ];

                            fputcsv($handle, array_map(
                                fn (mixed $value): string => $this->escapeSpreadsheetValue($value),
                                $row
                            ));
                        }
                    });
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function attendanceStatusLabel(?string $status): string
    {
        return match ($status) {
            'present' => 'Hadir',
            'late' => 'Terlambat',
            'permission' => 'Izin',
            'sick' => 'Sakit',
            'leave' => 'Cuti',
            'absent' => 'Alpa',
            'invalid' => 'Tidak Valid',
            'pending_review' => 'Perlu Ditinjau',
            default => $status ? ucfirst(str_replace('_', ' ', $status)) : '-',
        };
    }

    private function resolveMonth(Request $request): array
    {
        $value = $request->string('month', now(config('app.timezone'))->format('Y-m'))->toString();
        abort_unless(preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value) === 1, 422, 'Format bulan tidak valid.');

        [$year, $month] = array_map('intval', explode('-', $value));
        abort_unless($year >= 1000 && $year <= 9998, 422, 'Tahun laporan tidak didukung.');

        return [$year, $month];
    }

    private function monthRange(int $year, int $month): array
    {
        $start = CarbonImmutable::create(
            $year,
            $month,
            1,
            0,
            0,
            0,
            config('app.timezone')
        );

        return [$start->toDateString(), $start->addMonth()->toDateString()];
    }

    private function escapeSpreadsheetValue(mixed $value): string
    {
        $stringValue = trim((string) $value);

        if ($stringValue === '') {
            return '';
        }

        if ($stringValue === '-') {
            return $stringValue;
        }

        if (preg_match('/^[=+\-@]/', $stringValue) === 1) {
            return "'{$stringValue}";
        }

        return $stringValue;
    }
}
