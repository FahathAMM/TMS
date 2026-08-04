<?php

namespace App\Enums;

enum FabricSource: string
{
    case CustomerProvided = 'customer_provided';
    case InHouse          = 'in_house';

    public function label(): string
    {
        return match($this) {
            self::CustomerProvided => 'Customer Provided',
            self::InHouse          => 'In-House Inventory',
        };
    }
}
