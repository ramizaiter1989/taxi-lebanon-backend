<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\MessageTarget;
class FirebaseService
{
    protected $messaging;

    public function __construct()
    {
        try {
            $factory = (new Factory)->withServiceAccount(config('firebase.credentials'));
            $this->messaging = $factory->createMessaging();
        } catch (\Exception $e) {
            Log::error('Firebase initialization failed: ' . $e->getMessage());
            $this->messaging = null;
        }
    }

    /**
     * Send a notification to a single device.
     *
     * @param string $fcmToken
     * @param string $title
     * @param string $body
     * @param array $data
     * @return bool
     */
    public function sendToDevice(string $fcmToken, string $title, string $body, array $data = []): bool
    {
        if (!$this->messaging || empty($fcmToken)) {
            return false;
        }

        try {
            

            $message = CloudMessage::new()
                ->withTarget(MessageTarget::TOKEN, $fcmToken)
                ->withNotification(Notification::create($title, $body))
                ->withData($data);


            $this->messaging->send($message);

            return true;
        } catch (\Exception $e) {
            Log::error('Firebase notification failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send a notification to multiple devices.
     *
     * @param array $fcmTokens
     * @param string $title
     * @param string $body
     * @param array $data
     * @return bool
     */
    public function sendToMultipleDevices(array $fcmTokens, string $title, string $body, array $data = []): bool
    {
        if (!$this->messaging || empty($fcmTokens)) {
            return false;
        }

        try {
            $message = CloudMessage::new()
                ->withNotification(Notification::create($title, $body))
                ->withData($data);

            $this->messaging->sendMulticast($message, $fcmTokens);

            return true;
        } catch (\Exception $e) {
            Log::error('Firebase multicast failed: ' . $e->getMessage());
            return false;
        }
    }
}
