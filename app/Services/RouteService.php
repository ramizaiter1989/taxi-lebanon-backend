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

    public function getRouteInfo($startLng, $startLat, $endLng, $endLat)
    {
        if (!$startLng || !$startLat || !$endLng || !$endLat) {
            return null;
        }

        try {
            $response = Http::get($this->baseUrl, [
                'api_key' => $this->apiKey,
                'start' => "{$startLng},{$startLat}",
                'end' => "{$endLng},{$endLat}",
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'distance' => $data['routes'][0]['summary']['distance'] ?? null, // in meters
                    'duration' => $data['routes'][0]['summary']['duration'] ?? null, // in seconds
                ];
            }
        } catch (\Exception $e) {
            \Log::error("RouteService failed: " . $e->getMessage());
            return null;
        }

        return null;
    }
}
