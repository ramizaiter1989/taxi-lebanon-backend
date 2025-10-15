<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ride;
use App\Models\Payment;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use App\Notifications\RideNotification;

class PaymentController extends Controller
{



    // Add tip to a ride
public function addTip(Request $request, Ride $ride)
{
    $request->validate([
        'tip_amount' => 'required|numeric|min:0'
    ]);
    
    $ride->update(['tip' => $request->tip_amount]);
}

    // Process payment for a ride using Stripe
public function payRide(Request $request, Ride $ride)
{
    $request->validate([
        'payment_method_id' => 'required|string'
    ]);

    try {
        Stripe::setApiKey(env('STRIPE_SECRET'));
        $paymentIntent = PaymentIntent::create([
            'amount' => $ride->fare * 100,
            'currency' => 'usd',
            'payment_method' => $request->payment_method_id,
            'confirm' => true,
        ]);

        $payment = Payment::create([
            'ride_id' => $ride->id,
            'amount' => $ride->fare,
            'status' => 'paid',
            'payment_method' => 'stripe',
            'transaction_id' => $paymentIntent->id,
        ]);

        // Notify driver
        $ride->driver->user->notify(new RideNotification(
            'Payment Received',
            "You received \${$ride->fare} for ride #{$ride->id}",
            [
                'type' => 'payment_received',
                'ride_id' => $ride->id,
                'amount' => $ride->fare
            ]
        ));

        return response()->json([
            'payment' => $payment,
            'message' => 'Payment successful'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Payment failed: ' . $e->getMessage()
        ], 400);
    }
}



}
