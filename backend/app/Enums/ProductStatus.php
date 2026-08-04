<?php

namespace App\Enums;

enum ProductStatus: string
{
    case Active   = 'active';
    case Inactive = 'inactive';
    case Draft    = 'draft';
    case Archived = 'archived';

    public function label(): string
    {
        return match($this) {
            self::Active   => 'Active',
            self::Inactive => 'Inactive',
            self::Draft    => 'Draft',
            self::Archived => 'Archived',
        };
    }

    public function isPublishable(): bool
    {
        return $this === self::Active;
    }

    public static function default(): self
    {
        return self::Draft;
    }
}
