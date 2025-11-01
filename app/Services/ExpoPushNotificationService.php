<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExpoPushNotificationService
{
    protected $expoPushUrl = 'https://exp.host/--/api/v2/push/send';

    /**
     * Send push notification to multiple devices
     * 
     * @param array $pushTokens Array of Expo push tokens
     * @param string $title Notification title
     * @param string $body Notification body
     * @param array $data Additional data payload
     * @return bool Success status
     */
    public function sendPushNotification(array $pushTokens, string $title, string $body, array $data = []): bool
    {
        // Filter out invalid tokens
        $validTokens = array_filter($pushTokens, function($token) {
            return !empty($token) && str_starts_with($token, 'ExponentPushToken[');
        });

        if (empty($validTokens)) {
            Log::warning('No valid Expo push tokens provided');
            return false;
        }

        // Build messages array
        $messages = [];
        foreach ($validTokens as $token) {
            $messages[] = [
                'to' => $token,
                'sound' => 'default',
                'title' => $title,
                'body' => $body,
                'data' => $data,
                'priority' => 'high',
                'channelId' => 'default', // Android notification channel
            ];
        }

        try {
            Log::info('📤 Sending push notifications', [
                'count' => count($messages),
                'title' => $title
            ]);

            $response = Http::timeout(10)
                ->post($this->expoPushUrl, $messages);

            if ($response->successful()) {
                $responseData = $response->json();
                
                // Check for errors in the response
                $hasErrors = false;
                if (isset($responseData['data'])) {
                    foreach ($responseData['data'] as $result) {
                        if (isset($result['status']) && $result['status'] === 'error') {
                            $hasErrors = true;
                            Log::error('Expo push error', [
                                'error' => $result['message'] ?? 'Unknown error',
                                'details' => $result['details'] ?? []
                            ]);
                        }
                    }
                }

                if (!$hasErrors) {
                    Log::info('✅ Push notifications sent successfully', [
                        'response' => $responseData
                    ]);
                    return true;
                } else {
                    Log::warning('⚠️ Some push notifications failed', [
                        'response' => $responseData
                    ]);
                    return false;
                }
            } else {
                Log::error('❌ Expo API returned error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('❌ Exception while sending push notifications', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Send push notification to a single device
     * 
     * @param string $pushToken Single Expo push token
     * @param string $title Notification title
     * @param string $body Notification body
     * @param array $data Additional data payload
     * @return bool Success status
     */
    public function sendToDevice(string $pushToken, string $title, string $body, array $data = []): bool
    {
        return $this->sendPushNotification([$pushToken], $title, $body, $data);
    }

    /**
     * Validate Expo push token format
     * 
     * @param string $token Token to validate
     * @return bool Is valid
     */
    public function isValidToken(string $token): bool
    {
        return !empty($token) && str_starts_with($token, 'ExponentPushToken[');
    }

    /**
     * Send notification with custom sound and priority
     * 
     * @param array $pushTokens Array of Expo push tokens
     * @param string $title Notification title
     * @param string $body Notification body
     * @param array $data Additional data payload
     * @param string $sound Sound to play (default, null, or custom sound file)
     * @param string $priority Priority level (default, normal, high)
     * @return bool Success status
     */
    public function sendCustomNotification(
        array $pushTokens, 
        string $title, 
        string $body, 
        array $data = [],
        string $sound = 'default',
        string $priority = 'high'
    ): bool {
        $validTokens = array_filter($pushTokens, function($token) {
            return $this->isValidToken($token);
        });

        if (empty($validTokens)) {
            Log::warning('No valid Expo push tokens provided');
            return false;
        }

        $messages = [];
        foreach ($validTokens as $token) {
            $message = [
                'to' => $token,
                'title' => $title,
                'body' => $body,
                'data' => $data,
                'priority' => $priority,
                'channelId' => 'default',
            ];

            if ($sound !== null) {
                $message['sound'] = $sound;
            }

            $messages[] = $message;
        }

        try {
            $response = Http::timeout(10)->post($this->expoPushUrl, $messages);

            if ($response->successful()) {
                Log::info('✅ Custom push notifications sent successfully');
                return true;
            } else {
                Log::error('❌ Failed to send custom push notifications', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('❌ Exception while sending custom push notifications: ' . $e->getMessage());
            return false;
        }
    }
}