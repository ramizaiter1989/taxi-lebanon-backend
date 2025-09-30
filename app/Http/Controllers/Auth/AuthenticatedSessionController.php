<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login view (for browser).
     */
    public function create(): View
    {
        return view('auth.login'); // This points to resources/views/auth/login.blade.php
    }

    /**
     * Handle login request (API or Web).
     */
    public function store(Request $request)
{
    $credentials = $request->validate([
        'email' => ['required', 'string', 'email'],
        'password' => ['required', 'string'],
    ]);

    if (!Auth::attempt($credentials, $request->boolean('remember'))) {
        return $request->expectsJson()
            ? response()->json(['message' => 'Invalid login credentials'], 401)
            : back()->withErrors(['email' => 'Invalid login credentials']);
    }

    $user = $request->user();

    // 🚨 Block locked accounts
    if ($user->is_locked) {
        Auth::logout(); // immediately log them out

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Your account has been locked. Please contact support.'], 403);
        }

        throw ValidationException::withMessages([
            'email' => __('Your account has been locked. Please contact support.'),
        ]);
    }

    // For API (mobile apps), issue a token
    if ($request->expectsJson()) {
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user'    => $user,
            'token'   => $token,
        ], 200);
    }

    // For Web
    $request->session()->regenerate();

    return redirect()->intended('dashboard');
}


    /**
     * Handle logout
     */
    public function destroy(Request $request)
    {
        if ($request->expectsJson()) {
            $request->user()->currentAccessToken()->delete();
            return response()->json(['message' => 'Logged out']);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
