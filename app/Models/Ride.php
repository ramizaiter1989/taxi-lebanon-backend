<?php
// app/Models/Ride.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ride extends Model
{
    protected $fillable = [
        'passenger_id',
        'driver_id',
        'origin_lat',
        'origin_lng',
        'destination_lat',
        'destination_lng',
        'status',
        'fare',
        'distance',
        'duration',
        'current_driver_lat',
        'current_driver_lng',
        'cancellation_reason',
        'cancellation_note',
        'cancelled_by',
        'cancelled_at',
        'is_pool',
        'pool_discount_percentage',
        'promo_code_id',
        'discount',
        'final_fare',
        'sos_triggered',
        'sos_triggered_at',
    ];

    protected $casts = [
        'is_pool' => 'boolean',
        'sos_triggered' => 'boolean',
        'sos_triggered_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'created_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'arrived_at' => 'datetime',
    ];

    protected $appends = [
        'durations',
        'current_driver_lat',
        'current_driver_lng',
        'calculated_fare',
    ];

    // relations...
    public function passenger() { return $this->belongsTo(User::class, 'passenger_id'); }
    public function driver() { return $this->belongsTo(Driver::class, 'driver_id'); }
    public function rideLogs() { return $this->hasMany(RideLog::class); }

    public function getDurationsAttribute()
    {
        $log = $this->rideLogs()->latest()->first();
        return [
            'pickup' => $log->pickup_duration_seconds ?? 0,
            'trip'   => $log->trip_duration_seconds ?? 0,
            'total'  => $log->total_duration_seconds ?? 0,
        ];
    }

    public function getCurrentDriverLatAttribute()
    {
        return $this->rideLogs()->latest()->first()?->driver_lat ?? null;
    }

    public function getCurrentDriverLngAttribute()
    {
        return $this->rideLogs()->latest()->first()?->driver_lng ?? null;
    }

    // Expose the stored fare if present
    public function getCalculatedFareAttribute()
    {
        return $this->fare ?? 0;
    }

    /**
     * Instance calculate fare using the ride's distance/duration.
     * Returns the calculated fare (float) but does NOT blindly overwrite saved fare
     * unless $save = true.
     */
    public function calculateFare(bool $save = true): ?float
    {
        $settings = \App\Models\FareSettings::first();
        if (!$settings) return null;

        $distance = (float) $this->distance; // km
        $duration = (float) $this->duration; // minutes

        // if missing data, don't calculate
        if ($distance <= 0 || $duration <= 0) return null;

        $fare = $settings->base_fare
              + ($distance * $settings->per_km_rate)
              + ($duration * $settings->per_minute_rate);

        if ($fare < ($settings->minimum_fare ?? 0)) {
            $fare = $settings->minimum_fare;
        }

        $fare *= ($settings->peak_multiplier ?? 1);

        $fare = round($fare, 2);

        if ($save) {
            $this->fare = $fare;
            $this->save();
        }

        return $fare;
    }

    /**
     * Optionally recalculate fare if distance/duration changed.
     * Here we use saving to keep behavior predictable (you can switch to updating/upsert)
     */
    protected static function booted()
    {
        static::saving(function (Ride $ride) {
            // If distance/duration are dirty and both present -> recalc and set fare
            if ($ride->isDirty(['distance', 'duration'])) {
                if ($ride->distance && $ride->duration) {
                    $calculated = $ride->calculateFare(false); // calculate but don't save inside
                    if ($calculated !== null) {
                        $ride->fare = $calculated; // set before save so it's persisted in same query
                    }
                }
            }
        });
    }
}
