<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExpoPushNotificationService
{
    protected $expoPushUrl = 'https://exp.host/--/api/v2/push/send';

    public function sendPushNotification(array $pushTokens, string $title, string $body, array $data = [])
    {
        $messages = [];
        foreach ($pushTokens as $token) {
            if (!empty($token)) {
                $messages[] = [
                    'to' => $token,
                    'sound' => 'default',
                    'title' => $title,
                    'body' => $body,
                    'data' => $data,
                ];
            }
        }

        if (empty($messages)) {
            Log::warning('No valid push tokens provided.');
            return false;
        }

        try {
            $response = Http::post($this->expoPushUrl, ['messages' => $messages]);
            if ($response->successful()) {
                Log::info('Push notifications sent successfully.', ['response' => $response->json()]);
                return true;
            } else {
                Log::error('Failed to send push notifications.', ['response' => $response->body()]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Exception while sending push notifications: ' . $e->getMessage());
            return false;
        }
    }
}
