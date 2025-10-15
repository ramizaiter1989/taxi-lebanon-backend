<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request)
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'gender' => $this->gender,
            'profile_photo' => $this->profile_photo,
            'wallet_balance' => $this->wallet_balance,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];

        // Include driver details only if user is a driver
        if ($this->role === 'driver' && $this->driver) {
            $data['driver'] = [
                'vehicle_type' => $this->driver->vehicle_type,
                'vehicle_number' => $this->driver->vehicle_number,
                'license_number' => $this->driver->license_number,
                'rating' => $this->driver->rating,
                'availability_status' => $this->driver->availability_status,
                'car_photo' => $this->driver->car_photo,
                'current_driver_lat' => $this->driver->current_driver_lat,
                'current_driver_lng' => $this->driver->current_driver_lng,
            ];
        }

        return $data;
    }
}
