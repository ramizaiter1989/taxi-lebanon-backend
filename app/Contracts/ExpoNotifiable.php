<?php

namespace App\Contracts;

interface ExpoNotifiable
{
    /**
     * Get the Expo notification representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return void
     */
    public function toExpo($notifiable);
}