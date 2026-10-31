<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $fillable = ['name', 'start_time', 'end_time', 'check_in_open_minutes', 'late_tolerance_minutes', 'check_out_open_minutes', 'crosses_midnight'];

    protected function casts(): array
    {
        return ['crosses_midnight' => 'boolean'];
    }

    public function schedules()
    {
        return $this->hasMany(EmployeeSchedule::class);
    }
}
