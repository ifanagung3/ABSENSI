<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\LeaveRequest;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Mime\MimeTypes;

class LeaveRequestController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'approved', 'rejected', 'cancelled'])],
        ]);

        $requests = LeaveRequest::with(['employee.user', 'employee.department', 'reviewer'])
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.leave.index', compact('requests'));
    }

    public function review(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected'])],
            'review_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($data, $request, $leaveRequest): void {
            // Lock pegawai menjadi mutex bersama untuk store, cancel, review, dan
            // proses absensi pada pegawai yang sama.
            Employee::query()
                ->whereKey($leaveRequest->employee_id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedLeave = LeaveRequest::query()
                ->whereKey($leaveRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(
                $lockedLeave->status === 'pending',
                422,
                'Pengajuan ini sudah pernah ditinjau.'
            );

            $old = $lockedLeave->toArray();

            if ($data['status'] === 'approved') {
                $this->applyApproval($lockedLeave);
            }

            $lockedLeave->update([
                ...$data,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);

            $this->audit->record(
                $request,
                'leave.reviewed',
                $lockedLeave,
                $old,
                $lockedLeave->fresh()->toArray()
            );
        }, attempts: 3);

        return back()->with('success', 'Status pengajuan berhasil diperbarui.');
    }

    private function applyApproval(LeaveRequest $leaveRequest): void
    {
        $startDate = $leaveRequest->start_date->toDateString();
        $endExclusive = $leaveRequest->end_date->copy()->addDay()->toDateString();

        // Ambil row (bukan exists) agar SELECT ... FOR UPDATE benar-benar
        // mengunci record dan rentang indeks sebelum keputusan persetujuan.
        $attendanceRecords = AttendanceRecord::query()
            ->where('employee_id', $leaveRequest->employee_id)
            ->where('attendance_date', '>=', $startDate)
            ->where('attendance_date', '<', $endExclusive)
            ->lockForUpdate()
            ->get(['id', 'attendance_date', 'checked_in_at']);

        if ($attendanceRecords->contains(fn (AttendanceRecord $record) => $record->checked_in_at !== null)) {
            throw ValidationException::withMessages([
                'status' => 'Pengajuan tidak dapat disetujui karena sudah ada absensi masuk pada rentang tanggal tersebut.',
            ]);
        }

        $schedules = EmployeeSchedule::query()
            ->where('employee_id', $leaveRequest->employee_id)
            ->where('work_date', '>=', $startDate)
            ->where('work_date', '<', $endExclusive)
            ->where('status', 'working')
            ->lockForUpdate()
            ->get(['id', 'work_date']);

        if ($schedules->isEmpty()) {
            return;
        }

        $recordStatus = match ($leaveRequest->request_type) {
            'sick' => 'sick',
            'leave' => 'leave',
            default => 'permission',
        };
        $timestamp = now();
        $attendanceByDate = $attendanceRecords->keyBy(
            fn (AttendanceRecord $record): string => $record->attendance_date->toDateString()
        );

        EmployeeSchedule::query()
            ->whereKey($schedules->pluck('id'))
            ->update(['status' => 'leave']);

        AttendanceRecord::query()->upsert(
            $schedules->map(function (EmployeeSchedule $schedule) use (
                $attendanceByDate,
                $leaveRequest,
                $recordStatus,
                $timestamp
            ): array {
                $date = $schedule->work_date->toDateString();
                $existingRecord = $attendanceByDate->get($date);

                return [
                    'employee_id' => $leaveRequest->employee_id,
                    'schedule_id' => $schedule->id,
                    // Gunakan nilai mentah record lama agar SQLite tidak menganggap
                    // YYYY-MM-DD dan YYYY-MM-DD 00:00:00 sebagai dua unique key.
                    'attendance_date' => $existingRecord?->getRawOriginal('attendance_date') ?? $date,
                    'status' => $recordStatus,
                    'notes' => 'Dibuat dari pengajuan #'.$leaveRequest->id,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            })->all(),
            ['employee_id', 'attendance_date'],
            ['schedule_id', 'status', 'notes', 'updated_at'],
        );
    }

    public function attachment(LeaveRequest $leaveRequest): BinaryFileResponse
    {
        $relativePath = $leaveRequest->attachment_path;

        abort_if(blank($relativePath), 404, 'Lampiran tidak ditemukan.');

        $disk = Storage::disk('local');

        abort_unless($disk->exists($relativePath), 404, 'File lampiran tidak ditemukan.');

        $extension = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));

        abort_unless(in_array($extension, ['jpg', 'jpeg', 'png', 'pdf'], true), 415, 'Format lampiran tidak didukung.');

        $fileName = basename($relativePath);
        $mimeType = (new MimeTypes)->guessMimeType($disk->path($relativePath));

        $allowedMimeTypes = [
            'image/jpeg',
            'image/png',
            'application/pdf',
        ];

        abort_unless(in_array($mimeType, $allowedMimeTypes, true), 415, 'Format lampiran tidak didukung.');

        return response()->file($disk->path($relativePath), [
            'Content-Type' => $mimeType,
            'X-Content-Type-Options' => 'nosniff',
            'Content-Disposition' => 'inline; filename="'.$fileName.'"',
        ]);
    }
}
