<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GeocodingService
{
    protected $apiKey;
    protected $baseUrl;
    protected $cacheTime = 86400; // 24 hours

    public function __construct()
    {
        $this->apiKey = env('ORS_API_KEY');
        $this->baseUrl = 'https://api.openrouteservice.org/geocode/reverse';
    }

    /**
     * Get address from coordinates with caching
     */
    public function getAddress($lat, $lng)
    {
        if (!$lat || !$lng) {
            return 'Unknown location';
        }

        $cacheKey = "geocode_{$lat}_{$lng}";

        return Cache::remember($cacheKey, $this->cacheTime, function () use ($lat, $lng) {
            try {
                $response = Http::timeout(10)
                    ->withHeaders(['Authorization' => $this->apiKey])
                    ->get($this->baseUrl, [
                        'point.lon' => $lng,
                        'point.lat' => $lat,
                        'size' => 1,
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    if (isset($data['features'][0]['properties'])) {
                        return $this->formatAddress($data['features'][0]['properties']);
                    }
                }

                Log::warning('Geocoding API failed', [
                    'lat' => $lat,
                    'lng' => $lng,
                    'status' => $response->status(),
                ]);
            } catch (\Exception $e) {
                Log::error('Geocoding error: ' . $e->getMessage(), [
                    'lat' => $lat,
                    'lng' => $lng,
                ]);
            }

            return $this->getFallbackAddress($lat, $lng);
        });
    }

    /**
     * Batch get addresses for multiple coordinates
     */
    public function batchGetAddresses(array $coordinates)
    {
        $addresses = [];

        foreach ($coordinates as $coord) {
            if (is_array($coord) && count($coord) >= 2) {
                $addresses[] = $this->getAddress($coord[0], $coord[1]);
            } else {
                $addresses[] = 'Unknown location';
            }
        }

        return $addresses;
    }

    /**
     * Format address from API response
     */
    protected function formatAddress($properties)
    {
        $parts = [];

        // Get relevant address components
        if (!empty($properties['name'])) {
            $parts[] = $properties['name'];
        }
        if (!empty($properties['street'])) {
            $parts[] = $properties['street'];
        }
        if (!empty($properties['locality'])) {
            $parts[] = $properties['locality'];
        }
        if (!empty($properties['region'])) {
            $parts[] = $properties['region'];
        }
        if (!empty($properties['country'])) {
            $parts[] = $properties['country'];
        }

        // Fallback to label if available
        if (empty($parts) && !empty($properties['label'])) {
            return $properties['label'];
        }

        return !empty($parts) ? implode(', ', array_slice($parts, 0, 3)) : 'Unknown location';
    }

    /**
     * Fallback address when API fails
     */
    protected function getFallbackAddress($lat, $lng)
    {
        return sprintf('Location: %.6f, %.6f', $lat, $lng);
    }

    /**
     * Clear cached address
     */
    public function clearAddressCache($lat, $lng)
    {
        $cacheKey = "geocode_{$lat}_{$lng}";
        Cache::forget($cacheKey);
    }

    /**
     * Get address with custom cache time
     */
    public function getAddressWithCache($lat, $lng, $cacheTime = null)
    {
        $originalCacheTime = $this->cacheTime;
        if ($cacheTime !== null) {
            $this->cacheTime = $cacheTime;
        }

        $address = $this->getAddress($lat, $lng);

        $this->cacheTime = $originalCacheTime;
        return $address;
    }
}