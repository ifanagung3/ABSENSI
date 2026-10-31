<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Position;
use App\Models\Shift;
use App\Models\User;
use App\Models\WorkLocation;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('Seeder demo dilewati pada environment production.');

            return;
        }

        $department = Department::firstOrCreate(
            ['name' => 'Tata Usaha'],
            ['description' => 'Unit administrasi dan layanan sekolah']
        );
        $position = Position::firstOrCreate(
            ['name' => 'Staf Tata Usaha'],
            ['description' => 'Pegawai administrasi sekolah']
        );

        $location = WorkLocation::firstOrCreate(
            ['name' => 'SMKN 4 Pariaman'],
            [
                'address' => 'Jalan Tuanku Naali, Desa Taluk, Kecamatan Pariaman Selatan, Kota Pariaman, Sumatera Barat',
                'latitude' => -0.6447000,
                'longitude' => 100.1401000,
                'radius_meters' => 100,
                'maximum_accuracy_meters' => 100,
                'is_active' => true,
            ]
        );

        $shift = Shift::firstOrCreate(
            ['name' => 'Shift Reguler Sekolah'],
            [
                'start_time' => '07:30:00',
                'end_time' => '15:30:00',
                'check_in_open_minutes' => 90,
                'late_tolerance_minutes' => 10,
                'check_out_open_minutes' => 30,
                'crosses_midnight' => false,
            ]
        );

        User::firstOrCreate(
            ['email' => 'admin@hadirin.test'],
            [
                'name' => 'Administrator SMKN 4 Pariaman',
                'password' => 'password',
                'role' => 'admin',
                'is_active' => true,
                'must_change_password' => true,
            ]
        );

        $employeeUser = User::firstOrCreate(
            ['email' => 'pegawai@hadirin.test'],
            [
                'name' => 'Budi Santoso',
                'password' => 'password',
                'role' => 'employee',
                'is_active' => true,
                'must_change_password' => true,
            ]
        );

        $employee = Employee::firstOrCreate(
            ['user_id' => $employeeUser->id],
            [
                'department_id' => $department->id,
                'position_id' => $position->id,
                'default_location_id' => $location->id,
                'employee_number' => 'PGW-0001',
                'phone' => '081234567890',
                'join_date' => now()->subYear()->toDateString(),
                'employment_status' => 'permanent',
                'attendance_enabled' => true,
            ]
        );

        // Hari ini selalu dibuat agar dashboard demo langsung memiliki jadwal.
        foreach (range(0, 60) as $offset) {
            $date = now(config('attendance.timezone'))->addDays($offset);
            if ($offset !== 0 && $date->isWeekend()) {
                continue;
            }

            $existingSchedule = EmployeeSchedule::query()
                ->where('employee_id', $employee->id)
                ->whereDate('work_date', $date->toDateString())
                ->exists();

            if (! $existingSchedule) {
                EmployeeSchedule::create([
                    'employee_id' => $employee->id,
                    'work_date' => $date->toDateString(),
                    'shift_id' => $shift->id,
                    'work_location_id' => $location->id,
                    'status' => 'working',
                ]);
            }
        }
    }
}
