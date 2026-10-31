<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    protected $fillable = ['employee_id', 'schedule_id', 'attendance_date', 'status', 'checked_in_at', 'checked_out_at', 'late_minutes', 'early_leave_minutes', 'work_duration_minutes', 'notes', 'corrected_by'];

    protected function casts(): array
    {
        return ['attendance_date' => 'date', 'checked_in_at' => 'datetime', 'checked_out_at' => 'datetime'];
    }

    protected function attendanceDate(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => CarbonImmutable::parse($value)->toDateString(),
        );
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function schedule()
    {
        return $this->belongsTo(EmployeeSchedule::class, 'schedule_id');
    }

    public function events()
    {
        return $this->hasMany(AttendanceEvent::class);
    }

    public function corrector()
    {
        return $this->belongsTo(User::class, 'corrected_by');
    }
}
