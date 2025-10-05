<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    protected $fillable = [
        'user_id',
        'license_number',
        'vehicle_type',
        'vehicle_number',
        'current_driver_lat',
        'current_driver_lng',
        'scanning_range_km',
        'status',                // if you keep this separate from availability_status
        'availability_status',   // true/false (active/inactive)
        'rating',
        'car_photo',
        'car_photo_front',
        'car_photo_back',
        'car_photo_left',
        'car_photo_right',
        'license_photo',
        'id_photo',
        'insurance_photo',
        'active_at',
        'inactive_at',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function rides()
    {
        return $this->hasMany(Ride::class);
    }

    public function activeDurations()
    {
        return $this->hasMany(DriverActiveDuration::class);
    }
}
