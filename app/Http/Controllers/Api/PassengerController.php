<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Events\PassengerLocationUpdated;
use App\Models\Ride;
use App\Models\FavoritePlace;
use App\Models\EmergencyContact;
use App\Notifications\RideNotification;
use Illuminate\Support\Facades\Log;

class PassengerController extends Controller
{
    /**
     * Update passenger location
     */
    public function updateLocation(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        $user = $request->user();
        if ($user->role !== 'passenger') {
            return response()->json(['error' => 'Only passengers can update location'], 403);
        }

        $user->update([
            'current_lat' => $request->lat,
            'current_lng' => $request->lng,
            'last_location_update' => now(),
        ]);

        if($user->status){
            broadcast(new PassengerLocationUpdated($user))->toOthers();
        }

        return response()->json(['message' => 'Location updated']);
    }

    /**
 * Stream passenger location in real-time (called every few seconds from frontend)
 */
public function streamLocation(Request $request)
{
    $request->validate([
        'lat' => 'required|numeric|between:-90,90',
        'lng' => 'required|numeric|between:-180,180',
    ]);

    $user = $request->user();
    
    if ($user->role !== 'passenger') {
        return response()->json(['error' => 'Only passengers can stream location'], 403);
    }

    $user->update([
        'current_lat' => $request->lat,
        'current_lng' => $request->lng,
        'last_location_update' => now(),
    ]);

    if ($user->status) {
        broadcast(new PassengerLocationUpdated($user))->toOthers();
    }

    // Get active ride if exists
    $activeRide = Ride::where('passenger_id', $user->id)
        ->whereIn('status', ['accepted', 'in_progress', 'arrived'])
        ->with('driver.user')
        ->first();

    return response()->json([
        'message' => 'Location streamed',
        'has_active_ride' => $activeRide ? true : false,
        'ride_status' => $activeRide?->status,
        'driver' => $activeRide ? [
            'name' => $activeRide->driver->user->name,
            'phone' => $activeRide->driver->user->phone,
            'lat' => $activeRide->driver->current_driver_lat,
            'lng' => $activeRide->driver->current_driver_lng,
        ] : null,
    ]);
}

    /**
     * Get all rides for authenticated passenger
     */
    public function myRides(Request $request)
    {
        $rides = Ride::where('passenger_id', $request->user()->id)
                     ->with(['driver.user'])
                     ->latest()
                     ->get();
        
        return response()->json($rides);
    }

    /**
     * Get passenger profile
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        
        if ($user->role !== 'passenger') {
            return response()->json(['error' => 'Only passengers can access passenger profile'], 403);
        }

        return response()->json(['user' => $user]);
    }

    /**
     * Update passenger profile
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        
        if ($user->role !== 'passenger') {
            return response()->json(['error' => 'Only passengers can update passenger profile'], 403);
        }
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20|unique:users,phone,' . $user->id,
            'gender' => 'nullable|in:male,female',
            'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);
        
        if ($request->hasFile('profile_photo')) {
            $path = $request->file('profile_photo')->store('profiles', 'public');
            $validated['profile_photo'] = $path;
        }
        
        $user->update($validated);
        
        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);
    }

    /**
     * Admin fetches all online passengers
     */
    public function livePassengers()
    {
        $onlinePassengers = User::where('role', 'passenger')
            ->where('status', true)
            ->whereNotNull('current_lat')
            ->whereNotNull('current_lng')
            ->where('last_location_update', '>=', now()->subMinutes(5))
            ->get(['id','name','current_lat','current_lng','last_location_update']);

        return response()->json($onlinePassengers);
    }

    /**
     * Save favorite place
     */
    public function saveFavoritePlace(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'address' => 'required|string|max:255'
        ]);

        $favoritePlace = FavoritePlace::create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'lat' => $validated['lat'],
            'lng' => $validated['lng'],
            'address' => $validated['address']
        ]);

        return response()->json([
            'message' => 'Favorite place saved successfully',
            'place' => $favoritePlace
        ], 201);
    }

    /**
     * Get favorite places
     */
    public function getFavoritePlaces(Request $request)
    {
        $places = FavoritePlace::where('user_id', $request->user()->id)->get();
        return response()->json($places);
    }

    /**
     * Delete favorite place
     */
    public function deleteFavoritePlace(Request $request, FavoritePlace $place)
    {
        if ($place->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $place->delete();
        return response()->json(['message' => 'Favorite place deleted']);
    }

    /**
     * Emergency SOS
     */
    public function emergencySOS(Request $request, Ride $ride)
    {
        if ($ride->passenger_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Mark ride as SOS triggered
        $ride->update([
            'sos_triggered' => true,
            'sos_triggered_at' => now()
        ]);

        // Get emergency contacts
        $contacts = EmergencyContact::where('user_id', $request->user()->id)->get();

        // Send SMS to emergency contacts (using Vonage/Twilio)
        foreach ($contacts as $contact) {
            try {
                // Send SMS with location and ride details
                $message = "EMERGENCY ALERT: {$request->user()->name} has triggered SOS during a ride. "
                         . "Driver: {$ride->driver->user->name}, "
                         . "Location: https://maps.google.com/?q={$ride->current_driver_lat},{$ride->current_driver_lng}";
                
                // Implement your SMS service here
                // $this->sendSms($contact->phone, $message);
            } catch (\Exception $e) {
                Log::error("Failed to send SOS SMS: " . $e->getMessage());
            }
        }

        // Notify admin
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new RideNotification(
                'Emergency SOS Alert',
                "{$request->user()->name} triggered emergency SOS on ride #{$ride->id}",
                [
                    'type' => 'sos',
                    'ride_id' => $ride->id,
                    'passenger_id' => $request->user()->id,
                    'lat' => $ride->current_driver_lat,
                    'lng' => $ride->current_driver_lng
                ]
            ));
        }

        return response()->json([
            'message' => 'Emergency alert sent',
            'contacts_notified' => $contacts->count(),
            'live_location' => [
                'lat' => $ride->current_driver_lat,
                'lng' => $ride->current_driver_lng
            ]
        ]);
    }

    /**
     * Add emergency contact
     */
    public function addEmergencyContact(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'relationship' => 'nullable|string|max:50',
            'is_primary' => 'nullable|boolean'
        ]);

        $contact = EmergencyContact::create([
            'user_id' => $request->user()->id,
            ...$validated
        ]);

        return response()->json([
            'message' => 'Emergency contact added',
            'contact' => $contact
        ], 201);
    }

    /**
     * Get emergency contacts
     */
    public function getEmergencyContacts(Request $request)
    {
        $contacts = EmergencyContact::where('user_id', $request->user()->id)->get();
        return response()->json($contacts);
    }

    /**
     * Get wallet balance
     */
    public function getWalletBalance(Request $request)
    {
        return response()->json([
            'balance' => $request->user()->wallet_balance
        ]);
    }

    /**
     * Add to wallet (top-up)
     */
    public function addToWallet(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method_id' => 'required|string' // Stripe payment method
        ]);

        try {
            // Process payment via Stripe
            \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));
            
            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => $validated['amount'] * 100, // cents
                'currency' => 'usd',
                'payment_method' => $validated['payment_method_id'],
                'confirm' => true,
                'automatic_payment_methods' => [
                    'enabled' => true,
                    'allow_redirects' => 'never'
                ],
            ]);

            // Add to wallet
            $user = $request->user();
            $user->wallet_balance += $validated['amount'];
            $user->save();

            return response()->json([
                'message' => 'Wallet topped up successfully',
                'new_balance' => $user->wallet_balance,
                'transaction_id' => $paymentIntent->id
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Payment failed: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Update FCM token for push notifications
     */
    public function updateFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string'
        ]);

        $request->user()->update([
            'fcm_token' => $request->fcm_token
        ]);

        return response()->json(['message' => 'FCM token updated']);
    }
}