<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\GeocodingService;
use App\Services\RouteService;
use Illuminate\Support\Facades\Cache;

class RideResource extends JsonResource
{
    public function toArray($request)
    {
        $geocodingService = app(GeocodingService::class);
        $routeService = app(RouteService::class);

        // Cache addresses to avoid repeated API calls
        $originAddress = $this->getCachedAddress(
            $this->origin_lat,
            $this->origin_lng,
            $geocodingService
        );
        
        $destinationAddress = $this->getCachedAddress(
            $this->destination_lat,
            $this->destination_lng,
            $geocodingService
        );

        // Driver ETA (only if driver assigned and heading to pickup)
        $driverEta = null;
        if ($this->driver && 
            in_array($this->status, ['accepted', 'arrived']) &&
            $this->driver->current_driver_lat && 
            $this->driver->current_driver_lng) {
            
            $driverEta = $this->getCachedDriverEta($routeService);
        }

        // Trip duration and distance (use stored values if available)
        $tripInfo = $this->getTripInfo($routeService);

        return [
            'id' => $this->id,
            'status' => $this->status,
            'fare' => $this->fare ? round($this->fare, 2) : null,
            'distance' => $this->distance ? round($this->distance, 2) : null,
            'duration' => $this->duration ? round($this->duration, 1) : null,
            'is_pool' => $this->is_pool ?? false,
            
            'origin' => [
                'lat' => (float)$this->origin_lat,
                'lng' => (float)$this->origin_lng,
                'address' => $originAddress,
            ],
            
            'destination' => [
                'lat' => (float)$this->destination_lat,
                'lng' => (float)$this->destination_lng,
                'address' => $destinationAddress,
            ],
            
            'driver' => $this->whenLoaded('driver', function() use ($driverEta) {
                if (!$this->driver) return null;
                
                return [
                    'id' => $this->driver->id,
                    'vehicle_type' => $this->driver->vehicle_type,
                    'vehicle_model' => $this->driver->vehicle_model,
                    'vehicle_color' => $this->driver->vehicle_color,
                    'license_plate' => $this->driver->license_plate,
                    'rating' => $this->driver->rating ? round($this->driver->rating, 1) : null,
                    'total_rides' => $this->driver->total_rides ?? 0,
                    'availability_status' => $this->driver->availability_status,
                    'current_location' => [
                        'lat' => $this->driver->current_driver_lat ? (float)$this->driver->current_driver_lat : null,
                        'lng' => $this->driver->current_driver_lng ? (float)$this->driver->current_driver_lng : null,
                    ],
                    'eta_to_pickup' => $driverEta,
                    'user' => $this->driver->user ? [
                        'id' => $this->driver->user->id,
                        'name' => $this->driver->user->name,
                        'phone' => $this->shouldShowPhone() ? $this->driver->user->phone : null,
                        'email' => $this->shouldShowEmail() ? $this->driver->user->email : null,
                        'gender' => $this->driver->user->gender,
                        'profile_photo' => $this->driver->user->profile_photo,
                    ] : null,
                ];
            }),
            
            'trip_info' => $tripInfo,
            
            'passenger' => $this->whenLoaded('passenger', function() {
                if (!$this->passenger) return null;
                
                return [
                    'id' => $this->passenger->id,
                    'name' => $this->passenger->name,
                    'phone' => $this->shouldShowPhone() ? $this->passenger->phone : null,
                    'email' => $this->shouldShowEmail() ? $this->passenger->email : null,
                    'gender' => $this->passenger->gender,
                    'profile_photo' => $this->passenger->profile_photo,
                    'rating' => $this->passenger->rating ? round($this->passenger->rating, 1) : null,
                ];
            }),
            
            'current_location' => $this->getCurrentLocationInfo(),
            
            'timestamps' => [
                'requested_at' => $this->created_at?->toISOString(),
                'accepted_at' => $this->accepted_at?->toISOString(),
                'started_at' => $this->started_at?->toISOString(),
                'arrived_at' => $this->arrived_at?->toISOString(),
                'completed_at' => $this->completed_at?->toISOString(),
                'cancelled_at' => $this->cancelled_at?->toISOString(),
            ],
            
            'cancellation' => $this->when($this->status === 'cancelled', [
                'reason' => $this->cancellation_reason,
                'note' => $this->cancellation_note,
                'cancelled_by' => $this->cancelled_by,
            ]),
            
            'final_fare' => $this->status === 'completed' ? round($this->fare, 2) : null,
            
            'payment_status' => $this->payment_status ?? 'pending',
        ];
    }

    /**
     * Get cached address to avoid repeated geocoding calls
     */
    protected function getCachedAddress($lat, $lng, $geocodingService)
    {
        $cacheKey = "address_{$lat}_{$lng}";
        
        return Cache::remember($cacheKey, 3600, function () use ($lat, $lng, $geocodingService) {
            return $geocodingService->getAddress($lat, $lng) ?? 'Address unavailable';
        });
    }

    /**
     * Get cached driver ETA
     */
    protected function getCachedDriverEta($routeService)
    {
        if (!$this->driver || !$this->driver->current_driver_lat || !$this->driver->current_driver_lng) {
            return null;
        }

        $cacheKey = "driver_eta_{$this->id}_{$this->driver->id}";
        
        return Cache::remember($cacheKey, 180, function () use ($routeService) {
            $eta = $routeService->getRouteInfo(
                $this->driver->current_driver_lat,
                $this->driver->current_driver_lng,
                $this->origin_lat,
                $this->origin_lng
            );

            if ($eta) {
                return [
                    'distance_meters' => $eta['distance'],
                    'distance_km' => round($eta['distance'] / 1000, 2),
                    'duration_seconds' => $eta['duration'],
                    'duration_minutes' => round($eta['duration'] / 60, 1),
                    'duration_text' => $this->formatDuration($eta['duration']),
                ];
            }

            return null;
        });
    }

    /**
     * Get trip information (use stored values if available)
     */
    protected function getTripInfo($routeService)
    {
        // Use stored distance/duration if available (more efficient)
        if ($this->distance && $this->duration) {
            return [
                'distance_meters' => $this->distance * 1000,
                'distance_km' => round($this->distance, 2),
                'duration_seconds' => $this->duration * 60,
                'duration_minutes' => round($this->duration, 1),
                'duration_text' => $this->formatDuration($this->duration * 60),
            ];
        }

        // Fallback: calculate if not stored
        $cacheKey = "trip_info_{$this->origin_lat}_{$this->origin_lng}_{$this->destination_lat}_{$this->destination_lng}";
        
        return Cache::remember($cacheKey, 3600, function () use ($routeService) {
            $tripInfo = $routeService->getRouteInfo(
                $this->origin_lat,
                $this->origin_lng,
                $this->destination_lat,
                $this->destination_lng
            );

            if ($tripInfo) {
                return [
                    'distance_meters' => $tripInfo['distance'],
                    'distance_km' => round($tripInfo['distance'] / 1000, 2),
                    'duration_seconds' => $tripInfo['duration'],
                    'duration_minutes' => round($tripInfo['duration'] / 60, 1),
                    'duration_text' => $this->formatDuration($tripInfo['duration']),
                ];
            }

            // Fallback values
            return [
                'distance_meters' => 5000,
                'distance_km' => 5.0,
                'duration_seconds' => 600,
                'duration_minutes' => 10.0,
                'duration_text' => '10 mins',
            ];
        });
    }

    /**
     * Get current location info (for tracking during ride)
     * FIXED: Access driver location from driver relationship
     */
    protected function getCurrentLocationInfo()
    {
        // Check if ride is active AND driver exists AND has current location
        if (in_array($this->status, ['accepted', 'in_progress', 'arrived']) && 
            $this->driver && 
            $this->driver->current_driver_lat && 
            $this->driver->current_driver_lng) {
            return [
                'lat' => (float)$this->driver->current_driver_lat,
                'lng' => (float)$this->driver->current_driver_lng,
            ];
        }

        return null;
    }

    /**
     * Format duration in human-readable format
     */
    protected function formatDuration($seconds)
    {
        if ($seconds < 60) {
            return '< 1 min';
        }

        $minutes = round($seconds / 60);
        
        if ($minutes < 60) {
            return $minutes . ' min' . ($minutes > 1 ? 's' : '');
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        $text = $hours . ' hr' . ($hours > 1 ? 's' : '');
        if ($remainingMinutes > 0) {
            $text .= ' ' . $remainingMinutes . ' min';
        }
        
        return $text;
    }

    /**
     * Determine if phone should be shown (only during active ride)
     */
    protected function shouldShowPhone()
    {
        $user = request()->user();
        if (!$user) return false;

        // Show phone during active rides
        if (in_array($this->status, ['accepted', 'in_progress', 'arrived'])) {
            $isPassenger = $this->passenger_id === $user->id;
            $isDriver = $user->driver && $this->driver_id === $user->driver->id;
            return $isPassenger || $isDriver;
        }

        return false;
    }

    /**
     * Determine if email should be shown
     */
    protected function shouldShowEmail()
    {
        $user = request()->user();
        if (!$user) return false;

        // Only admins can see emails
        return $user->role === 'admin';
    }
}