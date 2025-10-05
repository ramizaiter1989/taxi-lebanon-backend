<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FareSettings;

class FareSettingsController extends Controller
{
    /**
     * Get current fare settings
     */
    public function index()
    {
        $settings = FareSettings::first();
        return response()->json($settings);
    }

    /**
     * Update fare settings
     */
    public function update(Request $request)
    {
        $request->validate([
            'base_fare' => 'required|numeric|min:0',
            'per_km_rate' => 'required|numeric|min:0',
            'per_minute_rate' => 'required|numeric|min:0',
        ]);

        $settings = FareSettings::first();

        if (!$settings) {
            $settings = FareSettings::create($request->only([
                'base_fare', 'per_km_rate', 'per_minute_rate'
            ]));
        } else {
            $settings->update($request->only([
                'base_fare', 'per_km_rate', 'per_minute_rate'
            ]));
        }

        return response()->json([
            'message' => 'Fare settings updated successfully',
            'settings' => $settings
        ]);
    }
}
