<?php

namespace App\Traits;

use Vonage\Client as VonageClient;
use Vonage\Client\Credentials\Basic as VonageBasic;
use Illuminate\Support\Facades\Log;

trait SendsOtpSms
{
    public function sendSms($phone, $otpCode)
    {
        $basic  = new VonageBasic(config('services.vonage.key'), config('services.vonage.secret'));
        $client = new VonageClient($basic);

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
}
