<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BenzConsumption extends Model
{
    protected $fillable = [
        'ride_id', 'distance_km', 'duration_min',
        'average_consumption_l_per_100km', 'fuel_price_per_liter',
        'fuel_used_liters', 'fuel_cost'
    ];

    public function ride() {
        return $this->belongsTo(Ride::class);
    }
}
