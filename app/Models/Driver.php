<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

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
        'last_location_update',

    ];

    protected $casts = [
        'availability_status' => 'boolean',
        'active_at' => 'datetime',
        'inactive_at' => 'datetime',
        'current_driver_lat' => 'decimal:7',
        'current_driver_lng' => 'decimal:7',
        'scanning_range_km' => 'decimal:7',
        'rating' => 'decimal:1',
    ];

    protected $attributes = [
        'availability_status' => true,
        'rating' => 5.0,
    ];

    // ✅ Check if profile is completed
    public function isProfileCompleted(): bool
    {
        return !empty($this->license_number)
            && !empty($this->vehicle_type)
            && !empty($this->vehicle_number);
    }

    // ✅ Relationships
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

    public function declinedRides()
    {
        return $this->hasMany(RideDecline::class);
    }

    // ✅ NEW: Go online
 public function goOnline()
{
    $this->update([
        'availability_status' => true,
        'active_at' => now(),
    ]);

    $this->activeDurations()->create([
        'active_at' => now(),
    ]);
}

    // ✅ NEW: Go offline
    public function goOffline()
{
    $this->update([
        'availability_status' => false,
        'inactive_at' => now(),
    ]);

    $activeDuration = $this->activeDurations()
        ->whereNull('inactive_at')
        ->latest('active_at')
        ->first();

    if ($activeDuration) {
        $activeDuration->update([
            'inactive_at' => now(),
            'duration_seconds' => now()->diffInSeconds($activeDuration->active_at),
        ]);
    }
}

    // ✅ NEW: Get total online time for today
    public function totalOnlineToday()
    {
        $today = Carbon::today();

        $seconds = $this->activeDurations()
            ->whereDate('active_at', $today)
            ->sum('duration_seconds');

        return gmdate('H:i:s', $seconds); // e.g. "03:25:42"
    }
}
