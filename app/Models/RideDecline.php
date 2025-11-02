<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RideDecline extends Model
{
    protected $fillable = ['ride_id', 'driver_id'];

    public function ride()
    {
        return $this->belongsTo(Ride::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}
