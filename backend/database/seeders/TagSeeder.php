<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        $tags = [
            'new-arrival', 'best-seller', 'on-sale', 'featured',
            'refurbished', 'bundle', 'limited-edition', 'eco-friendly',
            '5g', 'wireless', 'foldable', 'gaming',
            'pro', 'ultra', 'lite', 'max',
            'apple', 'samsung', 'xiaomi', 'oneplus',
            'iphone', 'galaxy', 'ipad', 'airpods',
            'fast-charging', 'magsafe', 'usb-c', 'waterproof',
            'accessories', 'cases', 'screen-protectors',
        ];

        foreach ($tags as $slug) {
            Tag::firstOrCreate(
                ['slug' => $slug],
                ['name' => ucwords(str_replace('-', ' ', $slug)), 'slug' => $slug]
            );
        }
    }
}
