<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DriverResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'vehicle_type' => $this->vehicle_type,
            'rating' => $this->rating,
            'availability_status' => $this->availability_status,
            'current_driver_lat' => $this->current_driver_lat,
            'current_driver_lng' => $this->current_driver_lng,
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}
