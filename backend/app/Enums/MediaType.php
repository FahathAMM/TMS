<?php

namespace App\Enums;

enum MediaType: string
{
    case Image = 'image';
    case Video = 'video';

    public function label(): string
    {
        return match($this) {
            self::Image => 'Image',
            self::Video => 'Video',
        };
    }

    public function mimeTypes(): array
    {
        return match($this) {
            self::Image => ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml'],
            self::Video => ['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/webm'],
        };
    }

    public function maxSizeMb(): int
    {
        return match($this) {
            self::Image => 10,
            self::Video => 100,
        };
    }
}
