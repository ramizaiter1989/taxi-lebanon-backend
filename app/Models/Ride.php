<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\FareSetting;

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

    // Auto-append attributes to JSON
    protected $appends = [
        'durations',
        'current_driver_lat',
        'current_driver_lng',
        'calculated_fare', // new
    ];

    public function passenger()
    {
        return $this->belongsTo(User::class, 'passenger_id');
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function payments()
    {
        return $this->hasOne(Payment::class);
    }

    public function rideLogs()
    {
        return $this->hasMany(RideLog::class);
    }

    public function fareSetting()
    {
        return $this->hasOne(FareSettings::class);
    }

    /**
     * Accessor: durations
     */
    public function getDurationsAttribute()
    {
        $log = $this->rideLogs()->latest()->first();

        return [
            'pickup' => $log->pickup_duration_seconds ?? 0,
            'trip'   => $log->trip_duration_seconds ?? 0,
            'total'  => $log->total_duration_seconds ?? 0,
        ];
    }

    /**
     * Accessors: current driver location
     */
    public function getCurrentDriverLatAttribute()
    {
        return $this->rideLogs()->latest()->first()?->driver_lat ?? null;
    }

    public function getCurrentDriverLngAttribute()
    {
        return $this->rideLogs()->latest()->first()?->driver_lng ?? null;
    }

    /**
     * Accessor: calculated fare
     */
    public function getCalculatedFareAttribute()
    {
        return $this->fare ?? 0;
    }
}
