<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Driver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration form (for web).
     */
    public function create(): View
    {
        return view('auth.register'); 
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(Request $request)
    {
        $request->validate([
            // User fields
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['nullable', 'string'],

            // Driver fields
            'license_number' => ['nullable', 'string', 'max:255'],
            'vehicle_type' => ['nullable', 'string', 'max:255'],
            'vehicle_number' => ['nullable', 'string', 'max:255'],
            'car_photo' => ['nullable', 'string', 'max:255'],
            'car_photo_front' => ['nullable', 'string', 'max:255'],
            'car_photo_back' => ['nullable', 'string', 'max:255'],
            'car_photo_left' => ['nullable', 'string', 'max:255'],
            'car_photo_right' => ['nullable', 'string', 'max:255'],
            'license_photo' => ['nullable', 'string', 'max:255'],
            'id_photo' => ['nullable', 'string', 'max:255'],
            'insurance_photo' => ['nullable', 'string', 'max:255'],
        ]);

        // Create the user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'passenger',
        ]);

        // If role is driver, create a driver record
        if (($request->role ?? 'passenger') === 'driver') {
            Driver::create([
                'user_id' => $user->id,
                'license_number' => $request->license_number,
                'vehicle_type' => $request->vehicle_type ?? 'car',
                'vehicle_number' => $request->vehicle_number,
                'car_photo' => $request->car_photo,
                'car_photo_front' => $request->car_photo_front,
                'car_photo_back' => $request->car_photo_back,
                'car_photo_left' => $request->car_photo_left,
                'car_photo_right' => $request->car_photo_right,
                'license_photo' => $request->license_photo,
                'id_photo' => $request->id_photo,
                'insurance_photo' => $request->insurance_photo,
            ]);
        }

        // Fire registered event
        event(new Registered($user));

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful. Please verify your email.',
            'user' => $user,
            'token' => $token,
        ]);
    }
}
