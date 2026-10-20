<?php

namespace App\Services;

class Geo
{
    /** Great-circle (straight-line) distance in kilometres. Not a road or travel distance. */
    public static function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $r = 6371.0088;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /** Link to OpenStreetMap's directions UI. Routing is computed by that site, not by this app. */
    public static function directionsUrl(float $lat1, float $lon1, float $lat2, float $lon2): string
    {
        return 'https://www.openstreetmap.org/directions?'.http_build_query([
            'route' => "{$lat1},{$lon1};{$lat2},{$lon2}",
        ], '', '&', PHP_QUERY_RFC3986);
    }
}
