<?php

namespace App\Notifications;

use App\Models\AlterationOrder;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AlterationOrderNotification extends Notification
{
    public function __construct(
        private readonly AlterationOrder $order,
        private readonly string          $headline,
        private readonly string          $body,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Add 'sms' here once a real gateway is wired to LoggedSmsChannel's
        // replacement — toSms() below is already implemented and ready.
        return $notifiable->email ? ['mail', 'database'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $garmentCount = $this->order->garments()->count();

        return (new MailMessage)
            ->subject("{$this->headline} — {$this->order->order_number}")
            ->greeting("Hi {$notifiable->name},")
            ->line($this->body)
            ->line("Order #: {$this->order->order_number}")
            ->line("Garments: {$garmentCount}")
            ->line("Total: {$this->order->total_amount}")
            ->line("Balance due: {$this->order->balance_due}")
            ->salutation('— Thank you for your business');
    }

    public function toSms(object $notifiable): string
    {
        return "{$this->headline}: {$this->body} (Order {$this->order->order_number})";
    }

    public function toArray(object $notifiable): array
    {
        return [
            'alteration_order_id' => $this->order->id,
            'order_number'        => $this->order->order_number,
            'headline'            => $this->headline,
            'body'                => $this->body,
        ];
    }
}
