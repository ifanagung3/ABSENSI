<?php

namespace App\Services;

class GeofenceService
{
    private const EARTH_RADIUS_METERS = 6371000;

    public function distanceMeters(
        float $latitudeA,
        float $longitudeA,
        float $latitudeB,
        float $longitudeB
    ): float {
        $latFrom = deg2rad($latitudeA);
        $latTo = deg2rad($latitudeB);
        $latDelta = deg2rad($latitudeB - $latitudeA);
        $lonDelta = deg2rad($longitudeB - $longitudeA);

        $a = sin($latDelta / 2) ** 2
            + cos($latFrom) * cos($latTo) * sin($lonDelta / 2) ** 2;

        $a = min(1.0, max(0.0, $a));
        $angle = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round(self::EARTH_RADIUS_METERS * $angle, 2);
    }
}
