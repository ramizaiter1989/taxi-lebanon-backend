<?php
//app/Services/GeocodingService.php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class GeocodingService
{
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = env('ORS_API_KEY');
        $this->baseUrl = 'https://api.openrouteservice.org/geocode/reverse';
    }

   // app/Services/GeocodingService.php


public function getAddress($lat, $lng)
{
    if (!$lat || !$lng) {
        return null;
    }

    $cacheKey = "geocode:{$lat},{$lng}";

    return Cache::remember($cacheKey, now()->addDays(7), function () use ($lat, $lng) {
        try {
            $response = Http::get($this->baseUrl, [
                'api_key' => $this->apiKey,
                'point.lon' => $lng,
                'point.lat' => $lat,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['features'][0]['properties']['label'] ?? null;
            }
        } catch (\Exception $e) {
            \Log::error("Geocoding failed: " . $e->getMessage());
            return null;
        }

        return null;
    });
}

}
