<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkLocation extends Model
{
    protected $fillable = ['name', 'address', 'latitude', 'longitude', 'radius_meters', 'maximum_accuracy_meters', 'is_active'];

    protected function casts(): array
    {
        return ['latitude' => 'decimal:7', 'longitude' => 'decimal:7', 'is_active' => 'boolean'];
    }

    public function schedules()
    {
        return $this->hasMany(EmployeeSchedule::class);
    }
}
