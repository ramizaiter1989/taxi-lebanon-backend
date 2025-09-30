<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class FareSettings extends Model
{
   protected $fillable = [
        'base_fare', 'per_km_rate', 'per_minute_rate', 'minimum_fare', 'cancellation_fee', 'peak_multiplier'
    ];

    // Optional: make it singleton
    public static function getSettings()
    {
        return self::first(); // assuming only 1 row exists
    }
}
