<?php

namespace App\Enums;

enum OrderType: string
{
    case CustomStitching = 'custom_stitching';
    case Alteration      = 'alteration';

    public function label(): string
    {
        return match($this) {
            self::CustomStitching => 'Custom Stitching',
            self::Alteration      => 'Alteration',
        };
    }
}
