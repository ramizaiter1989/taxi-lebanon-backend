<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BenzConsumption;
use App\Models\Ride;
use App\Models\FuelConfiguration;
use Illuminate\Http\Request;

class BenzConsumptionController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'ride_id' => 'required|exists:rides,id',
        ]);

        $ride = Ride::findOrFail($request->ride_id);
        $config = FuelConfiguration::latest()->first();

        $distance = $ride->distance_km;
        $duration = $ride->duration_min;

        $fuelUsed = ($distance * $config->average_consumption_l_per_100km) / 100;
        $fuelCost = $fuelUsed * $config->fuel_price_per_liter;

        $consumption = BenzConsumption::create([
            'ride_id' => $ride->id,
            'distance_km' => $distance,
            'duration_min' => $duration,
            'average_consumption_l_per_100km' => $config->average_consumption_l_per_100km,
            'fuel_price_per_liter' => $config->fuel_price_per_liter,
            'fuel_used_liters' => $fuelUsed,
            'fuel_cost' => $fuelCost,
        ]);

        return response()->json([
            'message' => 'Fuel consumption recorded successfully',
            'data' => $consumption,
        ], 201);
    }

    public function index()
    {
        return BenzConsumption::with('ride')->get();
    }
}

