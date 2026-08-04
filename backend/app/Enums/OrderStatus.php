<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending          = 'pending';
    case Quoted           = 'quoted';
    case DepositPaid      = 'deposit_paid';
    case InProduction     = 'in_production';
    case ReadyForFitting  = 'ready_for_fitting';
    case Completed        = 'completed';
    case Cancelled        = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::Pending         => 'Pending',
            self::Quoted          => 'Quoted',
            self::DepositPaid     => 'Deposit Paid',
            self::InProduction    => 'In Production',
            self::ReadyForFitting => 'Ready for Fitting',
            self::Completed       => 'Completed',
            self::Cancelled       => 'Cancelled',
        };
    }
}
