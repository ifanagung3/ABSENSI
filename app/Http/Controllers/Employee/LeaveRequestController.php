<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Services\AuditService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Mime\MimeTypes;
use Throwable;

class LeaveRequestController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        $employee = $request->user()->employee()->firstOrFail();
        $requests = $employee->leaveRequests()->latest()->paginate(10);

        return view('employee.leave.index', compact('requests'));
    }

    public function store(Request $request): RedirectResponse
    {
        $employee = $request->user()->employee()->firstOrFail();

        $data = $request->validate([
            'request_type' => ['required', Rule::in(['permission', 'sick', 'leave'])],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ], [
            'attachment.mimes' => 'Lampiran hanya boleh JPG, JPEG, PNG, atau PDF.',
            'attachment.max' => 'Ukuran lampiran maksimal 5 MB.',
        ]);

        $startDate = CarbonImmutable::parse($data['start_date']);
        $endDate = CarbonImmutable::parse($data['end_date']);
        $maximumLeaveDays = max(1, (int) config('attendance.maximum_leave_days', 366));

        if (($startDate->diffInDays($endDate) + 1) > $maximumLeaveDays) {
            throw ValidationException::withMessages([
                'end_date' => "Durasi pengajuan maksimal {$maximumLeaveDays} hari.",
            ]);
        }

        $storedPath = $request->file('attachment')?->store("leave-attachments/{$employee->id}", 'local');
        $path = is_string($storedPath) ? $storedPath : null;

        try {
            DB::transaction(function () use ($data, $employee, $path, $request): void {
                // Semua mutasi izin pegawai mengambil lock yang sama agar pemeriksaan
                // bentrok tetap benar ketika dua pengajuan dikirim bersamaan.
                $lockedEmployee = Employee::query()
                    ->whereKey($employee->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $overlap = $lockedEmployee->leaveRequests()
                    ->whereIn('status', ['pending', 'approved'])
                    ->whereDate('start_date', '<=', $data['end_date'])
                    ->whereDate('end_date', '>=', $data['start_date'])
                    ->lockForUpdate()
                    ->get(['id'])
                    ->isNotEmpty();

                if ($overlap) {
                    throw ValidationException::withMessages([
                        'start_date' => 'Sudah ada pengajuan aktif pada rentang tanggal tersebut.',
                    ]);
                }

                $leave = $lockedEmployee->leaveRequests()->create([
                    'request_type' => $data['request_type'],
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'],
                    'reason' => $data['reason'],
                    'attachment_path' => $path,
                    'status' => 'pending',
                ]);

                $this->audit->record($request, 'leave.created', $leave, null, $leave->toArray());
            }, attempts: 3);
        } catch (Throwable $exception) {
            if ($path && Storage::disk('local')->exists($path)) {
                Storage::disk('local')->delete($path);
            }

            throw $exception;
        }

        return back()->with('success', 'Pengajuan berhasil dikirim.');
    }

    public function cancel(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $employeeId = $request->user()->employee?->id;

        abort_unless($leaveRequest->employee_id === $employeeId, 403);

        DB::transaction(function () use ($employeeId, $leaveRequest, $request): void {
            Employee::query()
                ->whereKey($employeeId)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedLeave = LeaveRequest::query()
                ->whereKey($leaveRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($lockedLeave->employee_id === $employeeId, 403);
            abort_unless(
                $lockedLeave->status === 'pending',
                422,
                'Hanya pengajuan yang masih menunggu yang dapat dibatalkan.'
            );

            $old = $lockedLeave->toArray();
            $lockedLeave->update(['status' => 'cancelled']);
            $this->audit->record(
                $request,
                'leave.cancelled',
                $lockedLeave,
                $old,
                $lockedLeave->fresh()->toArray()
            );
        }, attempts: 3);

        return back()->with('success', 'Pengajuan dibatalkan.');
    }

    public function attachment(Request $request, LeaveRequest $leaveRequest): BinaryFileResponse
    {
        abort_unless($leaveRequest->employee_id === $request->user()->employee?->id, 403);

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
