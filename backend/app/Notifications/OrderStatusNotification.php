<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusNotification extends Notification
{
    public function __construct(
        private readonly Order  $order,
        private readonly string $headline,
        private readonly string $body,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $notifiable->email ? ['mail', 'database'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("{$this->headline} — Order {$this->order->order_number}")
            ->greeting("Hi {$notifiable->name},")
            ->line($this->body)
            ->line("Order #: {$this->order->order_number}")
            ->line("Total: {$this->order->total_amount}")
            ->line("Balance due: {$this->order->balance_due}")
            ->salutation('— Thank you for your business');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_id'     => $this->order->id,
            'order_number' => $this->order->order_number,
            'headline'     => $this->headline,
            'body'         => $this->body,
        ];
    }
}
