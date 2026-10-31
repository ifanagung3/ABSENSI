<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceEvent;
use App\Models\AttendanceRecord;
use App\Services\AuditService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['nullable', Rule::in(['present', 'late', 'absent', 'leave', 'sick', 'permission', 'pending_review', 'invalid'])],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $records = AttendanceRecord::with([
            'employee.user', 'employee.department', 'schedule.workLocation', 'events',
        ])
            ->when(isset($filters['date']), fn ($query) => $query->whereDate('attendance_date', $filters['date']))
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.$request->string('q')->toString().'%';
                $query->where(function ($sub) use ($term): void {
                    $sub->whereHas('employee.user', fn ($user) => $user->where('name', 'like', $term))
                        ->orWhereHas('employee', fn ($employee) => $employee->where('employee_number', 'like', $term));
                });
            })
            ->latest('attendance_date')
            ->latest('checked_in_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.attendance.index', compact('records'));
    }

    public function show(AttendanceRecord $attendanceRecord): View
    {
        $attendanceRecord->load([
            'employee.user', 'employee.department', 'employee.position',
            'schedule.shift', 'schedule.workLocation', 'events', 'corrector',
        ]);

        return view('admin.attendance.show', compact('attendanceRecord'));
    }

    public function review(Request $request, AttendanceRecord $attendanceRecord): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['present', 'late', 'pending_review', 'invalid'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($attendanceRecord, $data, $request): void {
            $lockedRecord = AttendanceRecord::query()
                ->whereKey($attendanceRecord->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $reviewEvents = $lockedRecord->events()
                ->where('validation_status', 'review')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($lockedRecord->status !== 'pending_review' || $reviewEvents->isEmpty()) {
                throw ValidationException::withMessages([
                    'attendance' => 'Absensi ini tidak lagi menunggu pemeriksaan atau tidak memiliki event yang dapat ditinjau.',
                ]);
            }

            $lateMinutes = $lockedRecord->late_minutes;
            if ($data['status'] === 'late') {
                $lateMinutes = $this->calculateLateMinutes($lockedRecord);
                if ($lateMinutes === 0) {
                    throw ValidationException::withMessages([
                        'status' => 'Status terlambat hanya dapat dipilih jika waktu masuk melewati batas toleransi shift.',
                    ]);
                }
            } elseif (in_array($data['status'], ['present', 'invalid'], true)) {
                $lateMinutes = 0;
            }

            $old = $lockedRecord->toArray() + [
                'review_event_ids' => $reviewEvents->modelKeys(),
                'review_event_status' => 'review',
            ];

            if ($data['status'] !== 'pending_review') {
                $reviewEvents->each->update([
                    'validation_status' => $data['status'] === 'invalid'
                        ? 'rejected'
                        : 'valid',
                ]);
            }

            $lockedRecord->update([
                'status' => $data['status'],
                'late_minutes' => $lateMinutes,
                'notes' => $data['notes'] ?? null,
                'corrected_by' => $request->user()->id,
            ]);

            $this->audit->record(
                $request,
                'attendance.reviewed',
                $lockedRecord,
                $old,
                $lockedRecord->fresh()->toArray() + [
                    'review_event_ids' => $reviewEvents->modelKeys(),
                    'review_event_status' => $data['status'] === 'pending_review'
                        ? 'review'
                        : ($data['status'] === 'invalid' ? 'rejected' : 'valid'),
                ]
            );
        }, attempts: 3);

        return back()->with('success', 'Status absensi berhasil diperbarui.');
    }

    private function calculateLateMinutes(AttendanceRecord $record): int
    {
        $record->loadMissing('schedule.shift');
        if (! $record->checked_in_at || ! $record->schedule?->shift) {
            return 0;
        }

        $shiftStart = CarbonImmutable::parse(
            $record->schedule->work_date->toDateString().' '.$record->schedule->shift->start_time,
            config('app.timezone')
        );
        $lateThreshold = $shiftStart->addMinutes(
            $record->schedule->shift->late_tolerance_minutes
        );
        $checkedInAt = CarbonImmutable::instance($record->checked_in_at)
            ->setTimezone(config('app.timezone'));

        return $checkedInAt->greaterThan($lateThreshold)
            ? (int) round($shiftStart->diffInMinutes($checkedInAt))
            : 0;
    }

    public function photo(AttendanceEvent $attendanceEvent): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($attendanceEvent->photo_path), 404);

        return Storage::disk('local')->response(
            $attendanceEvent->photo_path,
            basename($attendanceEvent->photo_path),
            [
                'Cache-Control' => 'private, max-age=300',
                'X-Content-Type-Options' => 'nosniff',
                'Content-Security-Policy' => "default-src 'none'",
            ]
        );
    }
}
