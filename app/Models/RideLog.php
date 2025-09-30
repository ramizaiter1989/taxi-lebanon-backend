<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RideLog extends Model
{
    protected $fillable = [
       'ride_id', 'driver_lat', 'driver_lng', 'passenger_lat', 'passenger_lng',
        'pickup_duration_seconds', 'trip_duration_seconds', 'total_duration_seconds'
    ];

    public function ride()
    {
        return $this->belongsTo(Ride::class);
    }
}
