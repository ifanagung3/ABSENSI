<?php

return [
    'school_name' => env('ATTENDANCE_SCHOOL_NAME', 'SMKN 4 Pariaman'),
    'timezone' => env('APP_TIMEZONE', 'Asia/Jakarta'),
    'enforce_time_window' => env('ATTENDANCE_ENFORCE_TIME_WINDOW', true),
    'maximum_device_clock_skew_minutes' => (int) env('ATTENDANCE_MAX_CLOCK_SKEW_MINUTES', 10),
    'duplicate_photo_review' => env('ATTENDANCE_DUPLICATE_PHOTO_REVIEW', true),
    'photo_max_kilobytes' => (int) env('ATTENDANCE_PHOTO_MAX_KB', 4096),
    'checkout_grace_hours' => (int) env('ATTENDANCE_CHECKOUT_GRACE_HOURS', 12),
    'maximum_bulk_schedule_days' => (int) env('ATTENDANCE_MAX_BULK_SCHEDULE_DAYS', 366),
    'maximum_bulk_schedule_employees' => (int) env('ATTENDANCE_MAX_BULK_EMPLOYEES', 500),
    'maximum_leave_days' => (int) env('ATTENDANCE_MAX_LEAVE_DAYS', 366),
];
