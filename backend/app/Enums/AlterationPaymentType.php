<?php

namespace App\Enums;

enum AlterationPaymentType: string
{
    case Advance = 'advance';
    case Balance = 'balance';
    case Full    = 'full';

    public function label(): string
    {
        return match($this) {
            self::Advance => 'Advance',
            self::Balance => 'Balance',
            self::Full    => 'Full Payment',
        };
    }
}
