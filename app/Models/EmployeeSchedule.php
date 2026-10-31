<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class EmployeeSchedule extends Model
{
    protected $fillable = ['employee_id', 'shift_id', 'work_location_id', 'work_date', 'status', 'notes'];

    protected function casts(): array
    {
        return ['work_date' => 'date'];
    }

    protected function workDate(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => CarbonImmutable::parse($value)->toDateString(),
        );
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function workLocation()
    {
        return $this->belongsTo(WorkLocation::class);
    }

    public function attendanceRecord()
    {
        return $this->hasOne(AttendanceRecord::class, 'schedule_id');
    }
}
