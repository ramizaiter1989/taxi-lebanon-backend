<?php

namespace App\Observers;

use App\Models\Ride;
use App\Models\RideLog;
use App\Models\FareSetting;
use App\Models\FareSettings;
use Carbon\Carbon;

class RideObserver
{
    /**
     * Handle the Ride "updating" event.
     */
    public function updating(Ride $ride): void
    {
        if ($ride->isDirty('status')) {
            $now = Carbon::now();

            switch ($ride->status) {
                case 'accepted':
                    $ride->accepted_at = $now;

                    // Create or update pickup duration in ride_logs
                    $log = $ride->rideLogs()->latest()->first() ?? new RideLog(['ride_id' => $ride->id]);
                    $createdAt = $ride->created_at instanceof Carbon ? $ride->created_at : Carbon::parse($ride->created_at);
                    $acceptedAt = $ride->accepted_at instanceof Carbon ? $ride->accepted_at : Carbon::parse($ride->accepted_at);
                    $log->pickup_duration_seconds = $acceptedAt->diffInSeconds($createdAt);
                    $log->save();
                    break;

                case 'in_progress':
                    $ride->started_at = $now;
                    break;

                case 'arrived':
                    $ride->arrived_at = $now;

                    // Update trip + total duration in ride_logs
                    $log = $ride->rideLogs()->latest()->first();
                    if ($log) {
                        if ($ride->started_at) {
                            $arrivedAt = $ride->arrived_at instanceof Carbon ? $ride->arrived_at : ($ride->arrived_at ? Carbon::parse($ride->arrived_at) : null);
                            $startedAt = $ride->started_at instanceof Carbon ? $ride->started_at : ($ride->started_at ? Carbon::parse($ride->started_at) : null);
                            if ($arrivedAt && $startedAt) {
                                $log->trip_duration_seconds = $arrivedAt->diffInSeconds($startedAt);
                            }
                        }
                        $log->total_duration_seconds =
                            ($log->pickup_duration_seconds ?? 0) +
                            ($log->trip_duration_seconds ?? 0);
                        $log->save();
                    }

                    // Fare calculation
                    $settings = FareSettings::first();
                    if ($settings) {
                        $distance = $ride->distance ?? 0;   // in km
                        $duration = $ride->duration ?? 0;   // in minutes

                        $fare = $settings->base_fare
                              + ($settings->per_km_rate * $distance)
                              + ($settings->per_minute_rate * $duration);

                        // Apply minimum fare
                        if ($fare < $settings->minimum_fare) {
                            $fare = $settings->minimum_fare;
                        }

                        // Apply peak multiplier
                        $fare *= $settings->peak_multiplier;

                        $ride->fare = round($fare, 2); // round to 2 decimals
                    }
                    break;

                case 'cancelled':
                    $ride->cancelled_at = $now;
                    // Optional: apply cancellation fee
                    $settings = FareSettings::first();
                    if ($settings) {
                        $ride->fare = $settings->cancellation_fee;
                    }
                    break;
            }
        }
    }

    /**
     * Handle the Ride "updated" event.
     */
    public function updated(Ride $ride): void
    {
        // Optional: notify passenger & driver, broadcast events, etc.
    }
}
