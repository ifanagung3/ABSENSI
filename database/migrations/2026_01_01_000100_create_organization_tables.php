<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('positions', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('work_locations', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('address');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedSmallInteger('radius_meters')->default(100);
            $table->unsignedSmallInteger('maximum_accuracy_meters')->default(100);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('shifts', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('check_in_open_minutes')->default(60);
            $table->unsignedSmallInteger('late_tolerance_minutes')->default(10);
            $table->unsignedSmallInteger('check_out_open_minutes')->default(30);
            $table->boolean('crosses_midnight')->default(false);
            $table->timestamps();
        });

        Schema::create('employees', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('default_location_id')->nullable()->constrained('work_locations')->nullOnDelete();
            $table->string('employee_number')->unique();
            $table->string('phone', 30)->nullable();
            $table->string('profile_photo_path')->nullable();
            $table->date('join_date')->nullable();
            $table->enum('employment_status', ['permanent', 'contract', 'intern', 'inactive'])->default('permanent');
            $table->boolean('attendance_enabled')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
        Schema::dropIfExists('shifts');
        Schema::dropIfExists('work_locations');
        Schema::dropIfExists('positions');
        Schema::dropIfExists('departments');
    }
};
