<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_schedules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shift_id')->constrained()->restrictOnDelete();
            $table->foreignId('work_location_id')->constrained()->restrictOnDelete();
            $table->date('work_date')->index();
            $table->enum('status', ['working', 'off', 'leave', 'holiday'])->default('working');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['employee_id', 'work_date']);
        });

        Schema::create('attendance_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('schedule_id')->nullable()->constrained('employee_schedules')->nullOnDelete();
            $table->date('attendance_date')->index();
            $table->enum('status', [
                'present', 'late', 'absent', 'leave', 'sick',
                'permission', 'pending_review', 'invalid',
            ])->default('present')->index();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->unsignedInteger('late_minutes')->default(0);
            $table->unsignedInteger('early_leave_minutes')->default(0);
            $table->unsignedInteger('work_duration_minutes')->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('corrected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['employee_id', 'attendance_date']);
        });

        Schema::create('attendance_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('attendance_record_id')->constrained()->cascadeOnDelete();
            $table->enum('event_type', ['check_in', 'check_out'])->index();
            $table->timestamp('server_time')->index();
            $table->timestamp('device_time')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('location_accuracy', 8, 2);
            $table->decimal('distance_meters', 10, 2);
            $table->boolean('inside_geofence');
            $table->string('photo_path');
            $table->string('photo_hash', 64)->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->enum('validation_status', ['valid', 'review', 'rejected'])->default('valid')->index();
            $table->text('rejection_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['attendance_record_id', 'event_type']);
        });

        Schema::create('leave_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->enum('request_type', ['permission', 'sick', 'leave']);
            $table->date('start_date');
            $table->date('end_date');
            $table->text('reason');
            $table->string('attachment_path')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 100)->index();
            $table->string('entity_type', 150)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('attendance_events');
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('employee_schedules');
    }
};
