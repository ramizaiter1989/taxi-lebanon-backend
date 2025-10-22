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

        // Get phase-specific route information
        $routeInfo = $this->getPhaseRouteInfo($routeService);

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
            
            'driver' => $this->whenLoaded('driver', function() {
                if (!$this->driver) return null;
                
                return [
                    'id' => $this->driver->id,
                    'vehicle_type' => $this->driver->vehicle_type ?? null,
                    'vehicle_number' => $this->driver->vehicle_number ?? null,
                    'license_number' => $this->driver->license_number ?? null,
                    'rating' => $this->driver->rating ? round($this->driver->rating, 1) : null,
                    'availability_status' => $this->driver->availability_status ?? false,
                    'current_location' => [
                        'lat' => $this->driver->current_driver_lat ? (float)$this->driver->current_driver_lat : null,
                        'lng' => $this->driver->current_driver_lng ? (float)$this->driver->current_driver_lng : null,
                    ],
                    'user' => $this->driver->user ? [
                        'id' => $this->driver->user->id,
                        'name' => $this->driver->user->name,
                        'phone' => $this->shouldShowPhone() ? $this->driver->user->phone : null,
                        'email' => $this->shouldShowEmail() ? $this->driver->user->email : null,
                        'gender' => $this->driver->user->gender ?? null,
                        'profile_photo' => $this->driver->user->profile_photo ?? null,
                    ] : null,
                ];
            }),
            
            // Phase-specific route information
            'route_info' => $routeInfo,
            
            'passenger' => $this->whenLoaded('passenger', function() {
                if (!$this->passenger) return null;
                
                return [
                    'id' => $this->passenger->id,
                    'name' => $this->passenger->name,
                    'phone' => $this->shouldShowPhone() ? $this->passenger->phone : null,
                    'email' => $this->shouldShowEmail() ? $this->passenger->email : null,
                    'gender' => $this->passenger->gender ?? null,
                    'profile_photo' => $this->passenger->profile_photo ?? null,
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
     * Get route information based on ride phase
     * 
     * Phases:
     * - pending: No route info (waiting for driver)
     * - accepted: Driver → Pickup (origin)
     * - arrived: Driver at pickup, show trip route (origin → destination)
     * - in_progress: Active trip (origin → destination)
     * - completed/cancelled: Show completed trip info
     */
    protected function getPhaseRouteInfo($routeService)
    {
        $phase = $this->getRidePhase();

        switch ($phase) {
            case 'to_pickup':
                // Driver accepted, heading to pickup
                return $this->getDriverToPickupInfo($routeService);
                
            case 'at_pickup':
                // Driver arrived at pickup, waiting for passenger
                return $this->getTripRouteInfo($routeService);
                
            case 'in_trip':
                // Ride in progress
                return $this->getTripRouteInfo($routeService);
                
            case 'completed':
                // Ride completed, show final stats
                return $this->getCompletedTripInfo();
                
            default:
                // Pending or other states
                return $this->getTripRouteInfo($routeService);
        }
    }

    /**
     * Determine current ride phase
     */
    protected function getRidePhase()
    {
        switch ($this->status) {
            case 'accepted':
                return 'to_pickup';
            case 'arrived':
                return 'at_pickup';
            case 'in_progress':
                return 'in_trip';
            case 'completed':
            case 'cancelled':
                return 'completed';
            default:
                return 'pending';
        }
    }

    /**
     * Get driver to pickup information (accepted phase)
     * Calculate: Driver's current location → Pickup (origin)
     */
    protected function getDriverToPickupInfo($routeService)
    {
        if (!$this->driver || 
            !$this->driver->current_driver_lat || 
            !$this->driver->current_driver_lng) {
            return $this->getTripRouteInfo($routeService); // Fallback
        }

        $cacheKey = "driver_to_pickup_{$this->id}_{$this->driver->current_driver_lat}_{$this->driver->current_driver_lng}";
        
        return Cache::remember($cacheKey, 60, function () use ($routeService) {
            try {
                $pickupRoute = $routeService->getRouteInfo(
                    $this->driver->current_driver_lat,
                    $this->driver->current_driver_lng,
                    $this->origin_lat,
                    $this->origin_lng
                );

                if ($pickupRoute) {
                    return [
                        'phase' => 'to_pickup',
                        'description' => 'Driver heading to pickup location',
                        'pickup_eta' => [
                            'distance_meters' => $pickupRoute['distance'],
                            'distance_km' => round($pickupRoute['distance'] / 1000, 2),
                            'duration_seconds' => $pickupRoute['duration'],
                            'duration_minutes' => round($pickupRoute['duration'] / 60, 1),
                            'duration_text' => $this->formatDuration($pickupRoute['duration']),
                        ],
                        'trip_route' => $this->getTripRouteInfo($routeService),
                    ];
                }
            } catch (\Exception $e) {
                \Log::error('Driver to pickup error: ' . $e->getMessage());
            }

            return $this->getTripRouteInfo($routeService);
        });
    }

    /**
     * Get trip route information (origin → destination)
     * Used for: arrived, in_progress phases
     */
    protected function getTripRouteInfo($routeService)
    {
        // Use stored distance/duration if available (ride completed)
        if ($this->distance && $this->duration && in_array($this->status, ['completed', 'cancelled'])) {
            return [
                'phase' => 'completed',
                'description' => 'Trip completed',
                'trip_route' => [
                    'distance_meters' => $this->distance * 1000,
                    'distance_km' => round($this->distance, 2),
                    'duration_seconds' => $this->duration * 60,
                    'duration_minutes' => round($this->duration, 1),
                    'duration_text' => $this->formatDuration($this->duration * 60),
                ],
            ];
        }

        // Calculate route: origin → destination
        $cacheKey = "trip_route_{$this->origin_lat}_{$this->origin_lng}_{$this->destination_lat}_{$this->destination_lng}";
        
        return Cache::remember($cacheKey, 3600, function () use ($routeService) {
            try {
                $tripRoute = $routeService->getRouteInfo(
                    $this->origin_lat,
                    $this->origin_lng,
                    $this->destination_lat,
                    $this->destination_lng
                );

                if ($tripRoute) {
                    $phase = $this->status === 'arrived' ? 'at_pickup' : 'in_trip';
                    $description = $this->status === 'arrived' 
                        ? 'Driver arrived at pickup' 
                        : 'Trip in progress';

                    return [
                        'phase' => $phase,
                        'description' => $description,
                        'trip_route' => [
                            'distance_meters' => $tripRoute['distance'],
                            'distance_km' => round($tripRoute['distance'] / 1000, 2),
                            'duration_seconds' => $tripRoute['duration'],
                            'duration_minutes' => round($tripRoute['duration'] / 60, 1),
                            'duration_text' => $this->formatDuration($tripRoute['duration']),
                        ],
                    ];
                }
            } catch (\Exception $e) {
                \Log::error('Trip route error: ' . $e->getMessage());
            }

            // Fallback values
            return [
                'phase' => 'pending',
                'description' => 'Calculating route',
                'trip_route' => [
                    'distance_meters' => 5000,
                    'distance_km' => 5.0,
                    'duration_seconds' => 600,
                    'duration_minutes' => 10.0,
                    'duration_text' => '10 mins',
                ],
            ];
        });
    }

    /**
     * Get completed trip information
     */
    protected function getCompletedTripInfo()
    {
        return [
            'phase' => 'completed',
            'description' => $this->status === 'completed' ? 'Trip completed' : 'Trip cancelled',
            'trip_route' => [
                'distance_meters' => $this->distance ? $this->distance * 1000 : 0,
                'distance_km' => $this->distance ? round($this->distance, 2) : 0,
                'duration_seconds' => $this->duration ? $this->duration * 60 : 0,
                'duration_minutes' => $this->duration ? round($this->duration, 1) : 0,
                'duration_text' => $this->duration ? $this->formatDuration($this->duration * 60) : 'N/A',
            ],
            'actual_duration' => $this->getActualTripDuration(),
        ];
    }

    /**
     * Calculate actual trip duration from timestamps
     */
    protected function getActualTripDuration()
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }

        $seconds = $this->started_at->diffInSeconds($this->completed_at);
        
        return [
            'seconds' => $seconds,
            'minutes' => round($seconds / 60, 1),
            'text' => $this->formatDuration($seconds),
        ];
    }

    /**
     * Get cached address to avoid repeated geocoding calls
     */
    protected function getCachedAddress($lat, $lng, $geocodingService)
    {
        $cacheKey = "address_{$lat}_{$lng}";
        
        return Cache::remember($cacheKey, 3600, function () use ($lat, $lng, $geocodingService) {
            try {
                return $geocodingService->getAddress($lat, $lng) ?? 'Address unavailable';
            } catch (\Exception $e) {
                return 'Address unavailable';
            }
        });
    }

    /**
     * Get current location info (for tracking during ride)
     * Gets location from driver relationship
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