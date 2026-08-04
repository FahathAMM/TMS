<?php

namespace App\Enums;

enum AttributeType: string
{
    case Select  = 'select';
    case Color   = 'color';
    case Size    = 'size';
    case Boolean = 'boolean';
    case Text    = 'text';

    public function label(): string
    {
        return match($this) {
            self::Select  => 'Select',
            self::Color   => 'Color',
            self::Size    => 'Size',
            self::Boolean => 'Boolean',
            self::Text    => 'Text',
        };
    }
}
