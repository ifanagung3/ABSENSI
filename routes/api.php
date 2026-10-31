<?php

use Illuminate\Support\Facades\Route;

Route::get('/status', fn () => response()->json([
    'application' => config('app.name'),
    'school' => config('attendance.school_name'),
    'status' => 'ok',
    'server_time' => now()->toIso8601String(),
]));
