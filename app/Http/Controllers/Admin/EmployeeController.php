<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use App\Models\WorkLocation;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        $employees = Employee::with(['user', 'department', 'position', 'defaultLocation'])
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.$request->string('q')->toString().'%';
                $query->where(function ($sub) use ($term): void {
                    $sub->where('employee_number', 'like', $term)
                        ->orWhereHas('user', fn ($user) => $user
                            ->where('name', 'like', $term)
                            ->orWhere('email', 'like', $term));
                });
            })
            ->when($request->filled('status'), function ($query) use ($request): void {
                $active = $request->string('status')->toString() === 'active';
                $query->where('attendance_enabled', $active);
            })
            ->orderBy('employee_number')
            ->paginate(15)
            ->withQueryString();

        $departments = Department::orderBy('name')->get();
        $positions = Position::orderBy('name')->get();
        $locations = WorkLocation::where('is_active', true)->orderBy('name')->get();

        return view('admin.employees.index', compact(
            'employees', 'departments', 'positions', 'locations'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('createEmployee', $this->rules());
        $temporaryPassword = Str::password(12, symbols: false);

        $employee = DB::transaction(function () use ($data, $request, $temporaryPassword): Employee {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $temporaryPassword,
                'role' => 'employee',
                'is_active' => true,
                'must_change_password' => true,
            ]);

            $employee = Employee::create([
                'user_id' => $user->id,
                'employee_number' => $data['employee_number'],
                'department_id' => $data['department_id'] ?? null,
                'position_id' => $data['position_id'] ?? null,
                'default_location_id' => $data['default_location_id'] ?? null,
                'phone' => $data['phone'] ?? null,
                'join_date' => $data['join_date'] ?? now()->toDateString(),
                'employment_status' => $data['employment_status'],
                'attendance_enabled' => true,
            ]);

            $this->audit->record($request, 'employee.created', $employee, null, [
                'employee_number' => $employee->employee_number,
                'email' => $employee->user->email,
            ]);

            return $employee;
        });

        return back()->with('success', "Pegawai berhasil ditambahkan. Password sementara: {$temporaryPassword}");
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $validator = validator($request->all(), $this->rules($employee));
        if ($validator->fails()) {
            return back()
                ->withErrors($validator, 'updateEmployee')
                ->withInput()
                ->with('editing_employee_id', $employee->id);
        }

        $data = $validator->validated();
        $old = $employee->load('user')->toArray();

        DB::transaction(function () use ($data, $employee, $old, $request): void {
            $employee->user->update([
                'name' => $data['name'],
                'email' => $data['email'],
            ]);
            $employee->update([
                'employee_number' => $data['employee_number'],
                'department_id' => $data['department_id'] ?? null,
                'position_id' => $data['position_id'] ?? null,
                'default_location_id' => $data['default_location_id'] ?? null,
                'phone' => $data['phone'] ?? null,
                'join_date' => $data['join_date'] ?? null,
                'employment_status' => $data['employment_status'],
            ]);
            $this->audit->record($request, 'employee.updated', $employee, $old, $employee->fresh('user')->toArray());
        });

        return back()->with('success', 'Data pegawai berhasil diperbarui.');
    }

    public function toggle(Request $request, Employee $employee): RedirectResponse
    {
        $old = ['attendance_enabled' => $employee->attendance_enabled, 'is_active' => $employee->user->is_active];
        $enabled = ! $employee->attendance_enabled;

        DB::transaction(function () use ($employee, $enabled, $old, $request): void {
            $employee->update(['attendance_enabled' => $enabled]);
            $employee->user->forceFill([
                'is_active' => $enabled,
                'session_version' => (int) $employee->user->session_version + 1,
            ]);
            $employee->user->setRememberToken(Str::random(60));
            $employee->user->save();
            $this->audit->record($request, 'employee.status_changed', $employee, $old, [
                'attendance_enabled' => $enabled,
                'is_active' => $enabled,
            ]);
        });

        return back()->with('success', 'Status akun pegawai diperbarui.');
    }

    public function resetPassword(Request $request, Employee $employee): RedirectResponse
    {
        $validator = validator(
            $request->all(),
            [
                'password' => ['required', 'confirmed', Password::min(8)->max(72)->letters()->numbers()],
            ],
            [
                'password.required' => 'Password baru wajib diisi.',
                'password.min' => 'Password minimal 8 karakter.',
                'password.max' => 'Password maksimal 72 karakter.',
                'password.confirmed' => 'Konfirmasi password tidak sama.',
            ]
        );

        if ($validator->fails()) {
            return back()
                ->withErrors($validator, 'resetPassword')
                ->with('password_employee_id', $employee->id);
        }

        $validated = $validator->validated();
        $employee->loadMissing('user');

        DB::transaction(function () use ($employee, $request, $validated): void {
            $employee->user->forceFill([
                'password' => $validated['password'],
                'must_change_password' => true,
                'session_version' => (int) $employee->user->session_version + 1,
            ]);
            $employee->user->setRememberToken(Str::random(60));
            $employee->user->save();

            $this->audit->record(
                $request,
                'employee.password_changed',
                $employee,
                null,
                [
                    'user_id' => $employee->user_id,
                    'password_changed' => true,
                    'sessions_revoked' => true,
                    'must_change_password' => true,
                ]
            );
        });

        return back()->with(
            'success',
            "Password {$employee->user->name} berhasil diubah."
        );
    }

    private function rules(?Employee $employee = null): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($employee?->user_id)],
            'employee_number' => ['required', 'string', 'max:50', Rule::unique('employees', 'employee_number')->ignore($employee)],
            'department_id' => ['nullable', 'exists:departments,id'],
            'position_id' => ['nullable', 'exists:positions,id'],
            'default_location_id' => [
                'nullable',
                Rule::exists('work_locations', 'id')->where('is_active', true),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'join_date' => ['nullable', 'date'],
            'employment_status' => ['required', Rule::in(['permanent', 'contract', 'intern', 'inactive'])],
        ];
    }
}
