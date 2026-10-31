<?php

namespace Tests\Feature;

use App\Models\EmployeeSchedule;
use App\Models\User;
use App\Models\WorkLocation;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_does_not_create_accounts_in_production(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('db:seed', [
            '--class' => DatabaseSeeder::class,
            '--force' => true,
        ])->assertSuccessful();

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('employee_schedules', 0);
    }

    public function test_running_demo_seeder_again_does_not_reset_credentials_or_business_state(): void
    {
        $this->seed(DatabaseSeeder::class);

        $employeeUser = User::where('email', 'pegawai@hadirin.test')->firstOrFail();
        $employeeUser->update(['password' => 'ChangedPassword123', 'must_change_password' => false]);
        $location = WorkLocation::where('name', 'SMKN 4 Pariaman')->firstOrFail();
        $location->update(['radius_meters' => 333]);
        $schedule = EmployeeSchedule::query()->firstOrFail();
        $schedule->update(['status' => 'leave']);

        $this->seed(DatabaseSeeder::class);

        $this->assertTrue(Hash::check(
            'ChangedPassword123',
            $employeeUser->fresh()->password
        ));
        $this->assertFalse($employeeUser->fresh()->must_change_password);
        $this->assertSame(333, $location->fresh()->radius_meters);
        $this->assertSame('leave', $schedule->fresh()->status);
    }
}
