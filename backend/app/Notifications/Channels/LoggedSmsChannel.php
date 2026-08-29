<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Placeholder SMS/WhatsApp channel. Logs the outgoing message instead of
 * calling a real gateway — swap the body of send() for a Twilio / WhatsApp
 * Business API call once credentials are available. A notification opts in
 * by adding 'sms' to its via() array and implementing toSms().
 */
class LoggedSmsChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toSms')) {
            return;
        }

        $phone = $notifiable->mobile ?? $notifiable->phone ?? null;
        if (!$phone) {
            return;
        }

        Log::info('[SMS stub] Would send SMS', [
            'to'      => $phone,
            'message' => $notification->toSms($notifiable),
        ]);
    }
}
