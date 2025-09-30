<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverActiveDuration extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'active_at',
        'inactive_at',
        'duration_seconds',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}
