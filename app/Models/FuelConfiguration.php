<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuelConfiguration extends Model
{
    protected $fillable = ['average_consumption_l_per_100km', 'fuel_price_per_liter'];
}
