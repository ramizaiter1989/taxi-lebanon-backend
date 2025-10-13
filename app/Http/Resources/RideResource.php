<?php
// app/Http/Resources/RideResource.php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\GeocodingService;
use App\Services\RouteService;

class RideResource extends JsonResource
{
    public function toArray($request)
    {
        $geocodingService = app(GeocodingService::class);
        $routeService = app(RouteService::class);

        // Calculate driver's ETA (driver's current location to origin)
        $driverEta = null;
        if ($this->driver && $this->driver->current_driver_lat && $this->driver->current_driver_lng) {
            $driverEta = $routeService->getRouteInfo(
                $this->driver->current_driver_lng,
                $this->driver->current_driver_lat,
                $this->origin_lng,
                $this->origin_lat
            );
        }

        // Calculate trip time (origin to destination)
        $tripTime = $routeService->getRouteInfo(
            $this->origin_lng,
            $this->origin_lat,
            $this->destination_lng,
            $this->destination_lat
        );

        return [
            'id' => $this->id,
            'status' => $this->status,
            'fare' => $this->fare,
            'origin' => [
                'lat' => (string)$this->origin_lat,
                'lng' => (string)$this->origin_lng,
                'address' => $geocodingService->getAddress($this->origin_lat, $this->origin_lng),
            ],
            'destination' => [
                'lat' => (string)$this->destination_lat,
                'lng' => (string)$this->destination_lng,
                'address' => $geocodingService->getAddress($this->destination_lat, $this->destination_lng),
            ],
            'driver' => $this->whenLoaded('driver', function() use ($driverEta) {
                return [
                    'id' => $this->driver->id,
                    'vehicle_type' => $this->driver->vehicle_type,
                    'rating' => (string)($this->driver->rating ?? '0.0'),
                    'availability_status' => $this->driver->availability_status,
                    'current_driver_lat' => (string)$this->driver->current_driver_lat,
                    'current_driver_lng' => (string)$this->driver->current_driver_lng,
                    'eta' => $driverEta ? [
                        'distance' => $driverEta['distance'],
                        'duration' => $driverEta['duration'],
                        'duration_text' => $driverEta['duration'] ? gmdate("i:s", $driverEta['duration']) : null,
                    ] : null,
                    'user' => $this->driver->user ? [
                        'id' => $this->driver->user->id,
                        'name' => $this->driver->user->name,
                        'email' => $this->driver->user->email,
                        'role' => $this->driver->user->role,
                        'gender' => $this->driver->user->gender,
                        'profile_photo' => $this->driver->user->profile_photo,
                    ] : null,
                ];
            }),
            'trip' => [
                'time' => $tripTime ? [
                    'distance' => $tripTime['distance'],
                    'duration' => $tripTime['duration'],
                    'duration_text' => $tripTime['duration'] ? gmdate("i:s", $tripTime['duration']) : null,
                ] : null,
            ],
            'passenger' => $this->whenLoaded('passenger', function() {
                return [
                    'id' => $this->passenger->id,
                    'name' => $this->passenger->name,
                    'email' => $this->passenger->email,
                    'role' => $this->passenger->role,
                    'gender' => $this->passenger->gender,
                ];
            }),
            'timestamps' => [
                'created_at' => $this->created_at,
                'started_at' => $this->started_at,
                'completed_at' => $this->completed_at,
            ],
        ];
    }
}
