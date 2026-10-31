<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = [
        'user_id', 'department_id', 'position_id', 'default_location_id',
        'employee_number', 'phone', 'profile_photo_path', 'join_date',
        'employment_status', 'attendance_enabled',
    ];

    protected function casts(): array
    {
        return [
            'join_date' => 'date',
            'attendance_enabled' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function defaultLocation()
    {
        return $this->belongsTo(WorkLocation::class, 'default_location_id');
    }

    public function schedules()
    {
        return $this->hasMany(EmployeeSchedule::class);
    }

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }
}
