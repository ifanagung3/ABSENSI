<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceEvent extends Model
{
    protected $fillable = ['attendance_record_id', 'event_type', 'server_time', 'device_time', 'latitude', 'longitude', 'location_accuracy', 'distance_meters', 'inside_geofence', 'photo_path', 'photo_hash', 'ip_address', 'user_agent', 'validation_status', 'rejection_reason', 'metadata'];

    protected function casts(): array
    {
        return ['server_time' => 'datetime', 'device_time' => 'datetime', 'inside_geofence' => 'boolean', 'metadata' => 'array'];
    }

    public function attendanceRecord()
    {
        return $this->belongsTo(AttendanceRecord::class);
    }
}
