<?php

namespace App\Enums;

enum AlterationGarmentStatus: string
{
    case Pending    = 'pending';
    case InProgress = 'in_progress';
    case Ready      = 'ready';
    case Delivered  = 'delivered';

    public function label(): string
    {
        return match($this) {
            self::Pending    => 'Pending',
            self::InProgress => 'In Progress',
            self::Ready      => 'Ready',
            self::Delivered  => 'Delivered',
        };
    }
}
