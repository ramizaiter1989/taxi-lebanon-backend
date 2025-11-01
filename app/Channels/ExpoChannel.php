<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use App\Services\ExpoPushNotificationService;
use Illuminate\Support\Facades\Log;

class ExpoChannel
{
    /**
     * The Expo Push Notification Service instance.
     *
     * @var \App\Services\ExpoPushNotificationService
     */
    protected $expoPushService;

    /**
     * Create a new Expo channel instance.
     *
     * @param  \App\Services\ExpoPushNotificationService  $expoPushService
     * @return void
     */
    public function __construct(ExpoPushNotificationService $expoPushService)
    {
        $this->expoPushService = $expoPushService;
    }

    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        // Check if the notification has a toExpo method
        if (!method_exists($notification, 'toExpo')) {
            Log::warning('Notification does not have toExpo method', [
                'notification' => get_class($notification),
                'notifiable_id' => $notifiable->id ?? null
            ]);
            return;
        }

        // Check if the notifiable has an Expo push token
        if (empty($notifiable->expo_push_token)) {
            Log::info('Notifiable does not have Expo push token', [
                'notifiable_type' => get_class($notifiable),
                'notifiable_id' => $notifiable->id ?? null
            ]);
            return;
        }

        try {
            // Call the toExpo method on the notification
            // @phpstan-ignore-next-line - Dynamic method call
            call_user_func([$notification, 'toExpo'], $notifiable);
            
            Log::debug('Expo notification sent via channel', [
                'notification' => get_class($notification),
                'notifiable_id' => $notifiable->id ?? null
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send Expo notification via channel', [
                'error' => $e->getMessage(),
                'notification' => get_class($notification),
                'notifiable_id' => $notifiable->id ?? null,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}