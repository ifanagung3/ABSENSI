<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    protected $fillable = ['employee_id', 'request_type', 'start_date', 'end_date', 'reason', 'attachment_path', 'status', 'reviewed_by', 'reviewed_at', 'review_notes'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'reviewed_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
