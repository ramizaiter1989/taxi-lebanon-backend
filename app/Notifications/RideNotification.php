<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Services\ExpoPushNotificationService;
use Illuminate\Support\Facades\Log;

class RideNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $title;
    protected $body;
    protected $data;

    public function __construct(string $title, string $body, array $data = [])
    {
        $this->title = $title;
        $this->body = $body;
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     * 
     * This tells Laravel to:
     * 1. Store in database
     * 2. Send via Expo push notification
     */
    public function via($notifiable): array
    {
        return ['database', 'expo'];
    }

    /**
     * Get the array representation for database storage.
     * This is ONLY for storing in the notifications table.
     */
    public function toArray($notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'data' => $this->data,
        ];
    }

    /**
     * Send Expo push notification.
     * This is called by the ExpoChannel.
     */
    public function toExpo($notifiable)
    {
        // Get the user's Expo push token
        $pushToken = $notifiable->expo_push_token;

        if (!$pushToken) {
            Log::warning("No Expo push token found for user {$notifiable->id}");
            return;
        }

        // Validate token format
        if (!str_starts_with($pushToken, 'ExponentPushToken[')) {
            Log::warning("Invalid Expo push token format for user {$notifiable->id}: {$pushToken}");
            return;
        }

        // Send push notification
        $expoPushService = app(ExpoPushNotificationService::class);
        
        try {
            $result = $expoPushService->sendPushNotification(
                [$pushToken],
                $this->title,
                $this->body,
                $this->data
            );
            
            if ($result) {
                Log::info("✅ Push notification sent successfully", [
                    'user_id' => $notifiable->id,
                    'title' => $this->title,
                    'type' => $this->data['type'] ?? 'unknown'
                ]);
            } else {
                Log::error("❌ Failed to send push notification", [
                    'user_id' => $notifiable->id,
                    'title' => $this->title
                ]);
            }
        } catch (\Exception $e) {
            Log::error("❌ Exception sending push notification", [
                'user_id' => $notifiable->id,
                'error' => $e->getMessage(),
                'title' => $this->title
            ]);
        }
    }
}