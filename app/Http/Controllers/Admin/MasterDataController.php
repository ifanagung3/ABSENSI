<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Position;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MasterDataController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(): View
    {
        $departments = Department::withCount('employees')->orderBy('name')->get();
        $positions = Position::withCount('employees')->orderBy('name')->get();

        return view('admin.master-data.index', compact('departments', 'positions'));
    }

    public function storeDepartment(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('createDepartment', [
            'name' => ['required', 'string', 'max:100', 'unique:departments,name'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
        DB::transaction(function () use ($data, $request): void {
            $department = Department::create($data);
            $this->audit->record($request, 'department.created', $department, null, $department->toArray());
        });

        return back()->with('success', 'Unit kerja berhasil ditambahkan.');
    }

    public function updateDepartment(Request $request, Department $department): RedirectResponse
    {
        $validator = validator($request->all(), [
            'name' => ['required', 'string', 'max:100', Rule::unique('departments', 'name')->ignore($department)],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
        if ($validator->fails()) {
            return back()
                ->withErrors($validator, 'updateDepartment')
                ->withInput()
                ->with('editing_department_id', $department->id);
        }

        $data = $validator->validated();
        $old = $department->toArray();
        DB::transaction(function () use ($data, $department, $old, $request): void {
            $department->update($data);
            $this->audit->record($request, 'department.updated', $department, $old, $department->fresh()->toArray());
        });

        return back()->with('success', 'Unit kerja berhasil diperbarui.');
    }

    public function destroyDepartment(Request $request, Department $department): RedirectResponse
    {
        if ($department->employees()->exists()) {
            return back()->withErrors(['department' => 'Unit kerja masih digunakan oleh pegawai.']);
        }
        $old = $department->toArray();
        $department->delete();
        $this->audit->record($request, 'department.deleted', null, $old, null);

        return back()->with('success', 'Unit kerja berhasil dihapus.');
    }

    public function storePosition(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('createPosition', [
            'name' => ['required', 'string', 'max:100', 'unique:positions,name'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
        DB::transaction(function () use ($data, $request): void {
            $position = Position::create($data);
            $this->audit->record($request, 'position.created', $position, null, $position->toArray());
        });

        return back()->with('success', 'Jabatan berhasil ditambahkan.');
    }

    public function updatePosition(Request $request, Position $position): RedirectResponse
    {
        $validator = validator($request->all(), [
            'name' => ['required', 'string', 'max:100', Rule::unique('positions', 'name')->ignore($position)],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
        if ($validator->fails()) {
            return back()
                ->withErrors($validator, 'updatePosition')
                ->withInput()
                ->with('editing_position_id', $position->id);
        }

        $data = $validator->validated();
        $old = $position->toArray();
        DB::transaction(function () use ($data, $position, $old, $request): void {
            $position->update($data);
            $this->audit->record($request, 'position.updated', $position, $old, $position->fresh()->toArray());
        });

        return back()->with('success', 'Jabatan berhasil diperbarui.');
    }

    public function destroyPosition(Request $request, Position $position): RedirectResponse
    {
        if ($position->employees()->exists()) {
            return back()->withErrors(['position' => 'Jabatan masih digunakan oleh pegawai.']);
        }
        $old = $position->toArray();
        $position->delete();
        $this->audit->record($request, 'position.deleted', null, $old, null);

        return back()->with('success', 'Jabatan berhasil dihapus.');
    }
}
