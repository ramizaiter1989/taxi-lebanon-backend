<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Http;

class RideResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'fare' => $this->fare,
            'origin' => [
                'lat' => (string)$this->origin_lat,
                'lng' => (string)$this->origin_lng,
                'address' => $this->getAddress($this->origin_lat, $this->origin_lng),
            ],
            'destination' => [
                'lat' => (string)$this->destination_lat,
                'lng' => (string)$this->destination_lng,
                'address' => $this->getAddress($this->destination_lat, $this->destination_lng),
            ],
            'driver' => $this->whenLoaded('driver', function() {
                return [
                    'id' => $this->driver->id,
                    'vehicle_type' => $this->driver->vehicle_type,
                    'rating' => (string)($this->driver->rating ?? '0.0'),
                    'availability_status' => $this->driver->availability_status,
                    'current_driver_lat' => (string)$this->driver->current_driver_lat,
                    'current_driver_lng' => (string)$this->driver->current_driver_lng,
                    'current_driver_address' => $this->getAddress($this->driver->current_driver_lat, $this->driver->current_driver_lng),
                    'user' => $this->driver->user ? [
                        'id' => $this->driver->user->id,
                        'name' => $this->driver->user->name,
                        'email' => $this->driver->user->email,
                        'role' => $this->driver->user->role,
                        'gender' => $this->driver->user->gender,
                        'profile_photo' => $this->driver->user->profile_photo,
                    ] : null
                ];
            }),
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

    protected function getAddress($lat, $lng)
    {
        // Example using OpenStreetMap Nominatim
        $response = Http::get("https://nominatim.openstreetmap.org/reverse", [
            'lat' => $lat,
            'lon' => $lng,
            'format' => 'json',
            'zoom' => 18,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return $data['display_name'] ?? 'Address not found';
        }

        return 'Address not found';
    }
}
