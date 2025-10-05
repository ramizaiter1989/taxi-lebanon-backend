<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Otp;
use Carbon\Carbon;
use Twilio\Rest\Client;
use App\Models\User;
use Vonage\Client as VonageClient;
use Vonage\Client\Credentials\Basic as VonageBasic;
use Illuminate\Support\Facades\Log;
use Exception;

class OtpController extends Controller
{
    private $otpLifetime = 5; // OTP valid for 5 minutes
    private $resendLimit = 3; // Max OTPs in time window
    private $resendWindow = 10; // Time window in minutes

    // Send OTP
public function sendOtp(Request $request)
{
    $request->validate([
        'phone' => 'required|string'
    ]);

    if ($this->isRateLimited($request->phone)) {
    return response()->json(['error' => 'Too many OTP requests. Please try again later.'], 429);
}


    $otpCode = rand(100000, 999999);

    Otp::create([
        'phone' => $request->phone,
        'code' => $otpCode,
        'expires_at' => Carbon::now()->addMinutes($this->otpLifetime)
    ]);

    try {
        $this->sendSms($request->phone, $otpCode);
    } catch (\Exception $e) {
        Log::error("OTP SMS failed: " . $e->getMessage());
        return response()->json(['error' => 'Failed to send OTP. Please try again later.'], 500);
    }

    return response()->json(['message' => 'OTP sent successfully']);
}

public function resendOtp(Request $request)
{
    $request->validate([
        'phone' => 'required|string'
    ]);

    if ($this->isRateLimited($request->phone)) {
    return response()->json(['error' => 'Too many OTP requests. Please try again later.'], 429);
}


    $otpCode = rand(100000, 999999);

    Otp::create([
        'phone' => $request->phone,
        'code' => $otpCode,
        'expires_at' => Carbon::now()->addMinutes($this->otpLifetime)
    ]);

    try {
    $this->sendSms($request->phone, $otpCode);
} catch (\Exception $e) {
    return response()->json(['error' => $e->getMessage()], 500);
}


    return response()->json(['message' => 'OTP resent successfully']);
}

private function isRateLimited($phone)
{
    $windowStart = Carbon::now()->subMinutes($this->resendWindow);
    $count = Otp::where('phone', $phone)
                ->where('created_at', '>=', $windowStart)
                ->count();

    return $count >= $this->resendLimit;
}



private function sendSms($phone, $otpCode)
{
    $basic  = new VonageBasic(config('services.vonage.key'), config('services.vonage.secret'));
    $client = new VonageClient($basic);

    // Ensure E.164 format
    if (!str_starts_with($phone, '+')) {
        $phone = preg_replace('/^00/', '+', $phone);
        if (!str_starts_with($phone, '+')) {
            $phone = '+' . $phone;
        }
    }

    $response = $client->sms()->send(
        new \Vonage\SMS\Message\SMS(
            $phone,
            config('services.vonage.from'),
            "Your SafeRide OTP code is $otpCode. It expires in 5 minutes."
        )
    );

    $message = $response->current();

    Log::info('Vonage SMS response: ', [
        'status' => $message->getStatus(),
        'message-id' => $message->getMessageId(),
    ]);

    if ($message->getStatus() != 0) {
        throw new \Exception("Vonage SMS failed with status: " . $message->getStatus());
    }
}


    // Verify OTP
  public function verifyOtp(Request $request)
{

    $request->merge([
        'phone' => trim($request->phone),
        'code'  => trim($request->code),
    ]);
    
    $request->validate([
        'phone' => 'required|string',
        'code' => 'required|string'
    ]);

    $otp = Otp::where('phone', $request->phone)
              ->where('code', $request->code)
              ->first();

    if (!$otp) {
        return response()->json(['error' => 'Invalid OTP'], 422);
    }

    if ($otp->isExpired()) {
        return response()->json(['error' => 'OTP expired'], 422);
    }

    $user = User::where('phone', $request->phone)->first();
    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }

    $user->is_verified = true;
    $user->save();

    $otp->delete();

    return response()->json(['message' => 'OTP verified successfully']);
}


}