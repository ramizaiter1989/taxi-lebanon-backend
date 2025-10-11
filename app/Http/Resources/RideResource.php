<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

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
            ],
            'destination' => [
                'lat' => (string)$this->destination_lat,
                'lng' => (string)$this->destination_lng,
            ],
            'driver' => $this->whenLoaded('driver', function() {
                return [
                    'id' => $this->driver->id,
                    'vehicle_type' => $this->driver->vehicle_type,
                    'rating' => (string)($this->driver->rating ?? '0.0'),
                    'availability_status' => $this->driver->availability_status,
                    'current_driver_lat' => (string)$this->driver->current_driver_lat,
                    'current_driver_lng' => (string)$this->driver->current_driver_lng,
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
                    // 'profile_photo' => $this->passenger->profile_photo,
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