<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\WorkLocation;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WorkLocationController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(): View
    {
        $locations = WorkLocation::withCount('schedules')->latest()->paginate(15);

        return view('admin.locations.index', compact('locations'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('createLocation', $this->rules());
        unset($data['detected_accuracy']);

        DB::transaction(function () use ($data, $request): void {
            $location = WorkLocation::create($data + ['is_active' => true]);
            $this->audit->record(
                $request,
                'location.created',
                $location,
                null,
                $location->toArray()
            );
        }, attempts: 3);

        return back()->with('success', 'Lokasi kerja berhasil dibuat.');
    }

    public function update(Request $request, WorkLocation $workLocation): RedirectResponse
    {
        try {
            $data = $request->validateWithBag(
                'updateLocation',
                $this->rules($workLocation)
            );
            unset($data['detected_accuracy']);

            DB::transaction(function () use ($data, $request, $workLocation): void {
                $lockedLocation = WorkLocation::query()
                    ->whereKey($workLocation->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();
                $scheduleIds = $lockedLocation->schedules()
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->pluck('id');

                if (
                    $scheduleIds->isNotEmpty()
                    && AttendanceRecord::query()->whereIn('schedule_id', $scheduleIds)->exists()
                ) {
                    throw ValidationException::withMessages([
                        'location' => 'Lokasi sudah digunakan pada absensi. Buat lokasi baru agar riwayat tidak berubah.',
                    ])->errorBag('updateLocation');
                }

                $old = $lockedLocation->toArray();
                $lockedLocation->update($data);
                $this->audit->record(
                    $request,
                    'location.updated',
                    $lockedLocation,
                    $old,
                    $lockedLocation->fresh()->toArray()
                );
            }, attempts: 3);
        } catch (ValidationException $exception) {
            $request->session()->flash('editing_location_id', $workLocation->getKey());
            throw $exception;
        }

        return back()->with('success', 'Lokasi kerja berhasil diperbarui.');
    }

    public function toggle(Request $request, WorkLocation $workLocation): RedirectResponse
    {
        DB::transaction(function () use ($request, $workLocation): void {
            $lockedLocation = WorkLocation::query()
                ->whereKey($workLocation->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            // Sinkronkan perubahan status dengan proses absensi yang mengunci jadwal.
            $lockedLocation->schedules()
                ->orderBy('id')
                ->lockForUpdate()
                ->pluck('id');

            $old = ['is_active' => $lockedLocation->is_active];
            $lockedLocation->update(['is_active' => ! $lockedLocation->is_active]);
            $this->audit->record(
                $request,
                'location.status_changed',
                $lockedLocation,
                $old,
                ['is_active' => $lockedLocation->is_active]
            );
        }, attempts: 3);

        return back()->with('success', 'Status lokasi berhasil diperbarui.');
    }

    private function rules(?WorkLocation $location = null): array
    {
        return [
            'name' => ['required', 'string', 'max:120', Rule::unique('work_locations', 'name')->ignore($location)],
            'address' => ['required', 'string', 'max:1000'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_meters' => ['required', 'integer', 'between:10,5000'],
            'maximum_accuracy_meters' => ['required', 'integer', 'between:5,1000'],
            'detected_accuracy' => ['nullable', 'numeric', 'min:0', 'max:10000'],
        ];
    }
}
