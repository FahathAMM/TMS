<?php

namespace App\Enums;

enum ProductionStatus: string
{
    case Pending   = 'pending';
    case Cutting   = 'cutting';
    case Stitching = 'stitching';
    case Qc        = 'qc';
    case Rework    = 'rework';
    case Ready     = 'ready';
    case Delivered = 'delivered';

    public function label(): string
    {
        return match($this) {
            self::Pending   => 'Pending',
            self::Cutting   => 'Cutting',
            self::Stitching => 'Stitching',
            self::Qc        => 'Quality Check',
            self::Rework    => 'Rework',
            self::Ready     => 'Ready',
            self::Delivered => 'Delivered',
        };
    }
}
