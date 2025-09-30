<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ride;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class PassengerController extends Controller
{
    // 🚕 Passenger map page
    public function index()
    {
        return view('passenger.map');
    }

    // 🚕 Request a ride
    public function storeRide(Request $request)
    {
        $request->validate([
            'destination_lat' => 'required|numeric',
            'destination_lng' => 'required|numeric',
        ]);

        Ride::create([
            'passenger_id'    => Auth::id(),
            'driver_id'       => null, // will be assigned later
            'origin_lat'      => $request->current_lat,
            'origin_lng'      => $request->current_lng,
            'destination_lat' => $request->destination_lat,
            'destination_lng' => $request->destination_lng,
            'status'          => 'pending'
        ]);

        return redirect()->back()->with('success', 'Ride requested successfully!');
    }

    // 🚕 Show all rides of the logged-in passenger
    public function myRides()
    {
        $rides = Ride::where('passenger_id', Auth::id())->latest()->get();

        return view('rides.myrides', compact('rides'));
    }

    // 👤 Show passenger profile
    public function profile()
    {
        $user = Auth::user();

        if ($user->role !== 'passenger') {
            abort(403, 'Unauthorized action.');
        }

        return view('passenger.profile', compact('user'));
    }

    // 👤 Update passenger profile
    public function updateProfile(Request $request)
{
    /** @var \App\Models\User $user */
    $user = Auth::user();

        $request->validate([
            'name'          => 'required|string|max:255',
            'phone'         => 'nullable|string|max:20|unique:users,phone,' . $user->id,
            'gender'        => 'in:male,female',
            'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $user->name   = $request->name;
        $user->phone  = $request->phone;
        $user->gender = $request->gender;

        if ($request->hasFile('profile_photo')) {
            $fileName = time() . '.' . $request->profile_photo->extension();
            $request->profile_photo->move(public_path('uploads/profile'), $fileName);
            $user->profile_photo = 'uploads/profile/' . $fileName;
        }

        $user->save();

        return redirect()->route('passenger.profile')->with('success', 'Profile updated successfully.');
    }
    
}
