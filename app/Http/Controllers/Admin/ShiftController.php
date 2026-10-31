<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Shift;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ShiftController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(): View
    {
        $shifts = Shift::withCount('schedules')->orderBy('start_time')->paginate(15);

        return view('admin.shifts.index', compact('shifts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request, 'createShift');

        DB::transaction(function () use ($data, $request): void {
            $shift = Shift::create($data);
            $this->audit->record($request, 'shift.created', $shift, null, $shift->toArray());
        }, attempts: 3);

        return back()->with('success', 'Shift berhasil dibuat.');
    }

    public function update(Request $request, Shift $shift): RedirectResponse
    {
        try {
            $data = $this->validatedData($request, 'updateShift', $shift);

            DB::transaction(function () use ($data, $request, $shift): void {
                $lockedShift = Shift::query()
                    ->whereKey($shift->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();
                $scheduleIds = $lockedShift->schedules()
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->pluck('id');

                if (
                    $scheduleIds->isNotEmpty()
                    && AttendanceRecord::query()->whereIn('schedule_id', $scheduleIds)->exists()
                ) {
                    throw ValidationException::withMessages([
                        'shift' => 'Shift sudah digunakan pada absensi. Buat shift baru agar riwayat tidak berubah.',
                    ])->errorBag('updateShift');
                }

                $old = $lockedShift->toArray();
                $lockedShift->update($data);
                $this->audit->record(
                    $request,
                    'shift.updated',
                    $lockedShift,
                    $old,
                    $lockedShift->fresh()->toArray()
                );
            }, attempts: 3);
        } catch (ValidationException $exception) {
            $request->session()->flash('editing_shift_id', $shift->getKey());
            throw $exception;
        }

        return back()->with('success', 'Shift berhasil diperbarui.');
    }

    public function destroy(Request $request, Shift $shift): RedirectResponse
    {
        DB::transaction(function () use ($request, $shift): void {
            $lockedShift = Shift::query()
                ->whereKey($shift->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedShift->schedules()->exists()) {
                throw ValidationException::withMessages([
                    'shift' => 'Shift masih digunakan pada jadwal dan tidak dapat dihapus.',
                ]);
            }

            $old = $lockedShift->toArray();
            $lockedShift->delete();
            $this->audit->record($request, 'shift.deleted', null, $old, null);
        }, attempts: 3);

        return back()->with('success', 'Shift berhasil dihapus.');
    }

    private function validatedData(
        Request $request,
        string $errorBag,
        ?Shift $shift = null
    ): array {
        $validator = Validator::make($request->all(), $this->rules($shift));
        $validator->after(function ($validator) use ($request): void {
            if ($validator->errors()->hasAny(['start_time', 'end_time'])) {
                return;
            }

            $start = $request->string('start_time')->toString();
            $end = $request->string('end_time')->toString();
            $crossesMidnight = $request->boolean('crosses_midnight');

            if ($start === $end) {
                $validator->errors()->add('end_time', 'Jam selesai harus berbeda dari jam mulai.');
            } elseif ($crossesMidnight && $end > $start) {
                $validator->errors()->add(
                    'crosses_midnight',
                    'Shift lintas hari harus memiliki jam selesai lebih awal dari jam mulai.'
                );
            } elseif (! $crossesMidnight && $end < $start) {
                $validator->errors()->add(
                    'crosses_midnight',
                    'Aktifkan opsi lintas hari karena jam selesai lebih awal dari jam mulai.'
                );
            }
        });

        $data = $validator->validateWithBag($errorBag);
        $data['start_time'] .= ':00';
        $data['end_time'] .= ':00';
        $data['crosses_midnight'] = $request->boolean('crosses_midnight');

        return $data;
    }

    private function rules(?Shift $shift = null): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('shifts', 'name')->ignore($shift)],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'check_in_open_minutes' => ['required', 'integer', 'between:0,720'],
            'late_tolerance_minutes' => ['required', 'integer', 'between:0,180'],
            'check_out_open_minutes' => ['required', 'integer', 'between:0,720'],
            'crosses_midnight' => ['nullable', 'boolean'],
        ];
    }
}
