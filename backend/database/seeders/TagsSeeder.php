<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagsSeeder extends Seeder
{
    public function run(): void
    {
        $tags = [
            'New Arrival', 'Best Seller', 'Trending', 'Hot Deal',
            'Limited Edition', 'On Sale', 'Featured', 'Clearance',
            'Premium', 'Budget Pick', 'Top Rated', "Editor's Choice",
            'Flash Sale', 'Most Loved', 'Staff Pick',
        ];

        foreach ($tags as $name) {
            Tag::firstOrCreate(['name' => $name]);
        }
    }
}
