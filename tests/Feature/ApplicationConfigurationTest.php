<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApplicationConfigurationTest extends TestCase
{
    public function test_application_and_attendance_use_the_same_timezone(): void
    {
        $this->assertSame(config('attendance.timezone'), config('app.timezone'));
        $this->assertSame(config('app.timezone'), date_default_timezone_get());
    }
}
