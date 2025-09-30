<?php

namespace App\Traits;

trait PolylineTrait {
    public function getRoutePolyline($startLat, $startLng, $endLat, $endLng)
    {
        $apiKey = env('ORS_API_KEY');
        if (!$apiKey) return [];

        $cacheKey = "route:{$startLat},{$startLng}-{$endLat},{$endLng}";

        return cache()->remember($cacheKey, now()->addMinutes(30), function () use ($startLat, $startLng, $endLat, $endLng, $apiKey) {
            $url = "https://api.openrouteservice.org/v2/directions/driving-car?api_key={$apiKey}&start={$startLng},{$startLat}&end={$endLng},{$endLat}";
            $response = @file_get_contents($url);
            if (!$response) return [];
            $data = json_decode($response, true);
            if (!isset($data['features'][0]['geometry']['coordinates'])) return [];
            return array_map(fn($c) => [$c[1], $c[0]], $data['features'][0]['geometry']['coordinates']);
        });
    }

    public function encodePolyline(array $coordinates, $precision = 5)
    {
        $factor = pow(10, $precision);
        $output = '';
        $prevLat = 0; $prevLng = 0;

        foreach ($coordinates as $point) {
            $lat = round($point[0] * $factor);
            $lng = round($point[1] * $factor);
            $dLat = $lat - $prevLat;
            $dLng = $lng - $prevLng;

            $encode = function($num) {
                $num = $num < 0 ? ~(($num << 1)) : ($num << 1);
                $out = '';
                while ($num >= 0x20) {
                    $out .= chr((0x20 | ($num & 0x1f)) + 63);
                    $num >>= 5;
                }
                return $out . chr($num + 63);
            };

            $output .= $encode($dLat) . $encode($dLng);
            $prevLat = $lat; $prevLng = $lng;
        }

        return $output;
    }
}
