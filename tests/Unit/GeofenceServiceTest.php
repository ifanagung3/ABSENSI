<?php

namespace Tests\Unit;

use App\Services\GeofenceService;
use PHPUnit\Framework\TestCase;

class GeofenceServiceTest extends TestCase
{
    public function test_same_coordinate_has_zero_distance(): void
    {
        $service = new GeofenceService;

        $this->assertSame(0.0, $service->distanceMeters(
            -0.6447, 100.1401, -0.6447, 100.1401
        ));
    }

    public function test_distance_is_calculated_in_meters(): void
    {
        $service = new GeofenceService;

        $distance = $service->distanceMeters(
            -0.644700, 100.140100,
            -0.644200, 100.140100
        );

        $this->assertGreaterThan(50, $distance);
        $this->assertLessThan(60, $distance);
    }
}
