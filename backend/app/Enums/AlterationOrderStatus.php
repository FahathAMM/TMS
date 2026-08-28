<?php

namespace App\Enums;

enum AlterationOrderStatus: string
{
    case Received   = 'received';
    case InProgress = 'in_progress';
    case Ready      = 'ready';
    case Delivered  = 'delivered';
    case Cancelled  = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::Received   => 'Received',
            self::InProgress => 'In Progress',
            self::Ready      => 'Ready for Pickup',
            self::Delivered  => 'Delivered',
            self::Cancelled  => 'Cancelled',
        };
    }
}
