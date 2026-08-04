<?php

namespace App\Enums;

enum PaymentType: string
{
    case Deposit = 'deposit';
    case Balance = 'balance';
    case Full    = 'full';

    public function label(): string
    {
        return match($this) {
            self::Deposit => 'Deposit',
            self::Balance => 'Balance',
            self::Full    => 'Full Payment',
        };
    }
}
