<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_schedules', function (Blueprint $table): void {
            $table->index(['work_date', 'status'], 'schedules_date_status_idx');
        });

        Schema::table('attendance_records', function (Blueprint $table): void {
            $table->index(['attendance_date', 'status'], 'attendance_date_status_idx');
        });

        Schema::table('leave_requests', function (Blueprint $table): void {
            $table->index(
                ['employee_id', 'status', 'start_date', 'end_date'],
                'leave_employee_status_dates_idx'
            );
        });

        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->index('created_at', 'audit_created_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->dropIndex('audit_created_at_idx');
        });

        Schema::table('leave_requests', function (Blueprint $table): void {
            $table->dropIndex('leave_employee_status_dates_idx');
        });

        Schema::table('attendance_records', function (Blueprint $table): void {
            $table->dropIndex('attendance_date_status_idx');
        });

        Schema::table('employee_schedules', function (Blueprint $table): void {
            $table->dropIndex('schedules_date_status_idx');
        });
    }
};
