<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class ProductTagSeeder extends Seeder
{
    // Curated tag assignments for flagship / noteworthy products
    private array $curated = [
        'iPhone 15 Pro Max'              => ['New Arrival', 'Best Seller', 'Featured', 'Premium'],
        'iPhone 15 Plus'                 => ['New Arrival', 'Featured', 'Top Rated'],
        'Samsung Galaxy S24 Ultra'       => ['New Arrival', 'Best Seller', 'Featured', 'Premium'],
        'Samsung Galaxy S24'             => ['New Arrival', 'Trending', 'Top Rated'],
        'Xiaomi 14 Pro'                  => ['New Arrival', 'Trending', "Editor's Choice"],
        'OnePlus 12'                     => ['Trending', 'Top Rated'],
        'iPad Pro 13" M4'                => ['New Arrival', 'Premium', 'Featured', "Editor's Choice"],
        'iPad Air 11" M2'                => ['New Arrival', 'Best Seller', 'Top Rated'],
        'Samsung Galaxy Buds3 Pro'       => ['Trending', 'Top Rated'],
        'MagSafe Leather Case iPhone 15' => ['Premium', 'Most Loved'],
        'Silicone Case iPhone 15'        => ['Best Seller', 'Most Loved'],
        'Apple 30W USB-C Charger'        => ['Best Seller', 'Top Rated'],
        'Apple MagSafe Charger 15W'      => ['Most Loved', 'Staff Pick'],
        'Anker 65W GaN USB-C Charger'    => ['Budget Pick', 'Top Rated', "Editor's Choice"],
        'Anker 100W 4-Port GaN Charger'  => ['Featured', 'Best Seller', "Editor's Choice"],
        '20000mAh Power Bank PD 65W'     => ['Featured', 'Best Seller', 'Staff Pick'],
        'Belkin BoostCharge Pro 3-in-1'  => ['Premium', 'Featured', 'Staff Pick'],
        'Tempered Glass Screen Protector iPhone 15' => ['Best Seller', 'Budget Pick'],
        'Samsung Galaxy Tab S10 Ultra'   => ['New Arrival', 'Premium', 'Trending'],
        'Xiaomi Redmi Note 13 Pro+'      => ['Budget Pick', 'Trending', "Editor's Choice"],
    ];

    public function run(): void
    {
        $tagsByName = Tag::all()->keyBy('name');
        $products   = Product::where('status', 'active')->get()->keyBy('name');

        // Curated assignments first
        foreach ($this->curated as $productName => $tagNames) {
            $product = $products[$productName] ?? null;
            if (!$product) continue;

            // Skip if product already has tags (idempotent)
            if ($product->tags()->exists()) continue;

            $tagIds = collect($tagNames)
                ->map(fn ($name) => $tagsByName[$name]->id ?? null)
                ->filter()
                ->values()
                ->all();

            $product->tags()->attach($tagIds);
        }

        // Random tags for any remaining products without tags
        $allTagIds = $tagsByName->pluck('id')->all();

        Product::where('status', 'active')
            ->whereDoesntHave('tags')
            ->each(function (Product $product) use ($allTagIds): void {
                shuffle($allTagIds);
                $product->tags()->attach(array_slice($allTagIds, 0, rand(2, 3)));
            });
    }
}
