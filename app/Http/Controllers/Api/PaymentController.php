<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ride;
use App\Models\Payment;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class PaymentController extends Controller
{
    public function payRide(Request $request, Ride $ride)
    {
        $request->validate([
            'payment_method_id' => 'required|string'
        ]);

        Stripe::setApiKey(env('STRIPE_SECRET'));

        $paymentIntent = PaymentIntent::create([
            'amount' => $ride->fare * 100, // in cents
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

        return response()->json($payment);
    }
}
