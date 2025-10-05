<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Services\FirebaseService;

class RideNotification extends Notification
{
    use Queueable;

    protected $title;
    protected $body;
    protected $data;

    public function __construct($title, $body, $data = [])
    {
        $this->title = $title;
        $this->body = $body;
        $this->data = $data;
    }

    public function via($notifiable)
    {
        return ['database']; // Store in database
    }

    public function toArray($notifiable)
    {
        // Also send Firebase notification
        if ($notifiable->fcm_token) {
            $firebase = new FirebaseService();
            $firebase->sendToDevice(
                $notifiable->fcm_token,
                $this->title,
                $this->body,
                $this->data
            );
        }

        return [
            'title' => $this->title,
            'body' => $this->body,
            'data' => $this->data
        ];
    }
}