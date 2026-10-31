<?php

use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\LeaveRequestController as AdminLeaveRequestController;
use App\Http\Controllers\Admin\MasterDataController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\ShiftController;
use App\Http\Controllers\Admin\WorkLocationController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\Employee\AttendanceController;
use App\Http\Controllers\Employee\DashboardController as EmployeeDashboardController;
use App\Http\Controllers\Employee\LeaveRequestController as EmployeeLeaveRequestController;
use App\Http\Controllers\Employee\ProfileController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [SessionController::class, 'create'])->name('login');
    Route::post('/login', [SessionController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');

    Route::get('/pegawai/login', fn () => redirect()->route('login'));
    Route::get('/admin/login', fn () => redirect()->route('login'));
});

Route::post('/logout', [SessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'role:employee'])
    ->prefix('pegawai')
    ->name('employee.')
    ->group(function (): void {
        Route::get('/dashboard', EmployeeDashboardController::class)->name('dashboard');
        Route::get('/absensi', [AttendanceController::class, 'capture'])->name('attendance.capture');
        Route::post('/absensi', [AttendanceController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('attendance.store');
        Route::get('/riwayat', [AttendanceController::class, 'history'])->name('attendance.history');

        Route::get('/izin', [EmployeeLeaveRequestController::class, 'index'])->name('leave.index');
        Route::post('/izin', [EmployeeLeaveRequestController::class, 'store'])
            ->middleware('throttle:5,1')
            ->name('leave.store');
        Route::patch('/izin/{leaveRequest}/cancel', [EmployeeLeaveRequestController::class, 'cancel'])->name('leave.cancel');
        Route::get('/izin/{leaveRequest}/lampiran', [EmployeeLeaveRequestController::class, 'attachment'])->name('leave.attachment');

        Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profil', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('/profil/password', [ProfileController::class, 'password'])
            ->middleware('throttle:5,1')
            ->name('profile.password');
    });

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');

        Route::get('/pegawai', [EmployeeController::class, 'index'])->name('employees.index');
        Route::post('/pegawai', [EmployeeController::class, 'store'])->name('employees.store');
        Route::patch('/pegawai/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
        Route::patch('/pegawai/{employee}/toggle', [EmployeeController::class, 'toggle'])->name('employees.toggle');
        Route::put('/pegawai/{employee}/reset-password', [EmployeeController::class, 'resetPassword'])
            ->middleware('throttle:5,1')
            ->name('employees.reset-password');

        Route::get('/master-data', [MasterDataController::class, 'index'])->name('master-data.index');
        Route::post('/master-data/departemen', [MasterDataController::class, 'storeDepartment'])->name('master-data.departments.store');
        Route::patch('/master-data/departemen/{department}', [MasterDataController::class, 'updateDepartment'])->name('master-data.departments.update');
        Route::delete('/master-data/departemen/{department}', [MasterDataController::class, 'destroyDepartment'])->name('master-data.departments.destroy');
        Route::post('/master-data/jabatan', [MasterDataController::class, 'storePosition'])->name('master-data.positions.store');
        Route::patch('/master-data/jabatan/{position}', [MasterDataController::class, 'updatePosition'])->name('master-data.positions.update');
        Route::delete('/master-data/jabatan/{position}', [MasterDataController::class, 'destroyPosition'])->name('master-data.positions.destroy');

        Route::get('/lokasi', [WorkLocationController::class, 'index'])->name('locations.index');
        Route::post('/lokasi', [WorkLocationController::class, 'store'])->name('locations.store');
        Route::patch('/lokasi/{workLocation}', [WorkLocationController::class, 'update'])->name('locations.update');
        Route::patch('/lokasi/{workLocation}/toggle', [WorkLocationController::class, 'toggle'])->name('locations.toggle');

        Route::get('/shift', [ShiftController::class, 'index'])->name('shifts.index');
        Route::post('/shift', [ShiftController::class, 'store'])->name('shifts.store');
        Route::patch('/shift/{shift}', [ShiftController::class, 'update'])->name('shifts.update');
        Route::delete('/shift/{shift}', [ShiftController::class, 'destroy'])->name('shifts.destroy');

        Route::get('/jadwal', [ScheduleController::class, 'index'])->name('schedules.index');
        Route::post('/jadwal', [ScheduleController::class, 'store'])->name('schedules.store');
        Route::post('/jadwal/bulk', [ScheduleController::class, 'bulkStore'])->name('schedules.bulk-store');
        Route::delete('/jadwal/{employeeSchedule}', [ScheduleController::class, 'destroy'])->name('schedules.destroy');

        Route::get('/absensi', [AdminAttendanceController::class, 'index'])->name('attendance.index');
        Route::get('/absensi/{attendanceRecord}', [AdminAttendanceController::class, 'show'])->name('attendance.show');
        Route::patch('/absensi/{attendanceRecord}/review', [AdminAttendanceController::class, 'review'])->name('attendance.review');
        Route::get('/absensi/event/{attendanceEvent}/foto', [AdminAttendanceController::class, 'photo'])->name('attendance.photo');

        Route::get('/izin', [AdminLeaveRequestController::class, 'index'])->name('leave.index');
        Route::patch('/izin/{leaveRequest}/review', [AdminLeaveRequestController::class, 'review'])->name('leave.review');
        Route::get('/izin/{leaveRequest}/lampiran', [AdminLeaveRequestController::class, 'attachment'])->name('leave.attachment');

        Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/laporan/export/csv', [ReportController::class, 'exportCsv'])->name('reports.csv');

        Route::get('/audit-log', AuditLogController::class)->name('audit.index');

        Route::get('/profil', [AdminProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profil', [AdminProfileController::class, 'update'])->name('profile.update');
        Route::put('/profil/password', [AdminProfileController::class, 'password'])
            ->middleware('throttle:5,1')
            ->name('profile.password');
    });
