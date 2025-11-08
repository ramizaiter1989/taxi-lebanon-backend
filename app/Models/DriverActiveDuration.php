<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverActiveDuration extends Model
{
    protected $fillable = [
        'driver_id',
        'active_at',
        'inactive_at',
        'duration_seconds',
    ];

    protected $casts = [
        'active_at' => 'datetime',
        'inactive_at' => 'datetime',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}
