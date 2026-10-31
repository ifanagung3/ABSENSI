<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Shift;
use App\Models\WorkLocation;
use App\Services\AuditService;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $date = $validated['date'] ?? now(config('app.timezone'))->toDateString();

        $schedules = EmployeeSchedule::with([
            'employee.user', 'shift', 'workLocation',
        ])->whereDate('work_date', $date)
            ->orderBy('employee_id')
            ->paginate(20)
            ->withQueryString();

        $employees = Employee::with('user')
            ->where('attendance_enabled', true)
            ->orderBy('employee_number')->get();
        $shifts = Shift::orderBy('name')->get();
        $locations = WorkLocation::where('is_active', true)->orderBy('name')->get();

        return view('admin.schedules.index', compact(
            'date', 'schedules', 'employees', 'shifts', 'locations'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('singleSchedule', $this->rules());

        DB::transaction(function () use ($data, $request): void {
            $schedule = EmployeeSchedule::query()
                ->where('employee_id', $data['employee_id'])
                ->where('work_date', $data['work_date'])
                ->lockForUpdate()
                ->first();

            if ($schedule?->attendanceRecord()->exists()) {
                throw ValidationException::withMessages([
                    'schedule' => 'Jadwal sudah memiliki data absensi dan tidak dapat diubah.',
                ])->errorBag('singleSchedule');
            }

            $old = $schedule?->toArray();
            $schedule ??= new EmployeeSchedule;
            $schedule->fill($data)->save();

            $this->audit->record(
                $request,
                'schedule.saved',
                $schedule,
                $old,
                $schedule->fresh()->toArray()
            );
        }, attempts: 3);

        return back()->with('success', 'Jadwal pegawai berhasil disimpan.');
    }

    public function bulkStore(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('bulkSchedule', [
            'employee_ids' => [
                'required',
                'array',
                'min:1',
                'max:'.config('attendance.maximum_bulk_schedule_employees', 500),
            ],
            'employee_ids.*' => ['integer', 'distinct', 'exists:employees,id'],
            'shift_id' => ['required', 'exists:shifts,id'],
            'work_location_id' => [
                'required',
                Rule::exists('work_locations', 'id')->where('is_active', true),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::in(['working', 'off', 'leave', 'holiday'])],
            'skip_weekends' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $startDate = CarbonImmutable::parse($data['start_date'])->startOfDay();
        $endDate = CarbonImmutable::parse($data['end_date'])->startOfDay();
        $maximumDays = config('attendance.maximum_bulk_schedule_days', 366);
        if ($startDate->diffInDays($endDate) + 1 > $maximumDays) {
            throw ValidationException::withMessages([
                'end_date' => "Rentang jadwal massal maksimal {$maximumDays} hari.",
            ])->errorBag('bulkSchedule');
        }

        $dates = [];
        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            if ($request->boolean('skip_weekends') && $date->isWeekend()) {
                continue;
            }
            $dates[] = $date->toDateString();
        }

        if ($dates === []) {
            throw ValidationException::withMessages([
                'start_date' => 'Tidak ada tanggal yang dapat dibuat setelah akhir pekan dilewati.',
            ])->errorBag('bulkSchedule');
        }

        $employeeIds = array_values(array_unique(array_map('intval', $data['employee_ids'])));
        $count = count($dates) * count($employeeIds);

        DB::transaction(function () use ($data, $dates, $employeeIds, $count, $request): void {
            $existingSchedules = EmployeeSchedule::query()
                ->whereIn('employee_id', $employeeIds)
                ->whereIn('work_date', $dates)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if (
                $existingSchedules->isNotEmpty()
                && AttendanceRecord::query()
                    ->whereIn('schedule_id', $existingSchedules->modelKeys())
                    ->exists()
            ) {
                throw ValidationException::withMessages([
                    'schedule' => 'Sebagian jadwal sudah memiliki data absensi. Tidak ada jadwal yang diubah.',
                ])->errorBag('bulkSchedule');
            }

            $rows = [];
            foreach ($dates as $date) {
                foreach ($employeeIds as $employeeId) {
                    $rows[] = [
                        'employee_id' => $employeeId,
                        'work_date' => $date,
                        'shift_id' => $data['shift_id'],
                        'work_location_id' => $data['work_location_id'],
                        'status' => $data['status'],
                        'notes' => $data['notes'] ?? null,
                    ];

                    if (count($rows) === 1000) {
                        $this->upsertSchedules($rows);
                        $rows = [];
                    }
                }
            }

            if ($rows !== []) {
                $this->upsertSchedules($rows);
            }

            $this->audit->record(
                $request,
                'schedule.bulk_saved',
                null,
                null,
                ['rows' => $count]
            );
        }, attempts: 3);

        return back()->with('success', "{$count} jadwal berhasil disimpan.");
    }

    public function destroy(Request $request, EmployeeSchedule $employeeSchedule): RedirectResponse
    {
        DB::transaction(function () use ($employeeSchedule, $request): void {
            $lockedSchedule = EmployeeSchedule::query()
                ->whereKey($employeeSchedule->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedSchedule->attendanceRecord()->exists()) {
                throw ValidationException::withMessages([
                    'schedule' => 'Jadwal sudah memiliki data absensi dan tidak dapat dihapus.',
                ]);
            }

            $old = $lockedSchedule->toArray();
            $lockedSchedule->delete();
            $this->audit->record($request, 'schedule.deleted', null, $old, null);
        }, attempts: 3);

        return back()->with('success', 'Jadwal berhasil dihapus.');
    }

    private function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'shift_id' => ['required', 'exists:shifts,id'],
            'work_location_id' => [
                'required',
                Rule::exists('work_locations', 'id')->where('is_active', true),
            ],
            'work_date' => ['required', 'date'],
            'status' => ['required', Rule::in(['working', 'off', 'leave', 'holiday'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    private function upsertSchedules(array $rows): void
    {
        EmployeeSchedule::upsert(
            $rows,
            ['employee_id', 'work_date'],
            ['shift_id', 'work_location_id', 'status', 'notes', 'updated_at']
        );
    }
}
