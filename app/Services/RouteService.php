<?php
// app/Services/RouteService.php
namespace App\Services;

use Illuminate\Support\Facades\Http;

class RouteService
{
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = env('ORS_API_KEY');
        $this->baseUrl = 'https://api.openrouteservice.org/v2/directions/driving-car';
    }

    // app/Services/RouteService.php
public function getRouteInfo($startLng, $startLat, $endLng, $endLat)
{
    if (!$startLng || !$startLat || !$endLng || !$endLat) {
        \Log::warning("Missing coordinates in RouteService");
        return null;
    }

    try {
        $response = Http::get($this->baseUrl, [
            'api_key' => $this->apiKey,
            'start' => "$startLng,$startLat",
            'end' => "$endLng,$endLat",
        ]);

        if ($response->successful()) {
            $data = $response->json();

            // Extract distance and duration from the first route's summary
            $distance = $data['features'][0]['properties']['segments'][0]['distance'] ?? null;
            $duration = $data['features'][0]['properties']['segments'][0]['duration'] ?? null;

            // Sum up all segments for total distance and duration
            $totalDistance = 0;
            $totalDuration = 0;

            foreach ($data['features'][0]['properties']['segments'] as $segment) {
                $totalDistance += $segment['distance'];
                $totalDuration += $segment['duration'];
            }

            return [
                'distance' => $totalDistance,
                'duration' => $totalDuration,
            ];
        } else {
            \Log::error("ORS API Error: " . $response->body());
            return null;
        }
    } catch (\Exception $e) {
        \Log::error("RouteService Exception: " . $e->getMessage());
        return null;
    }
}

}
