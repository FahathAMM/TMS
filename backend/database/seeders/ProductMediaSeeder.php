<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductMedia;
use Illuminate\Database\Seeder;

class ProductMediaSeeder extends Seeder
{
    // Curated picsum photo IDs that look like tech/product photography
    private array $photoIds = [
        10, 20, 30, 40, 50, 60, 70, 80, 90, 100,
        110, 120, 130, 140, 150, 160, 170, 180, 190, 200,
        201, 202, 203, 204, 205, 206, 207, 208, 209, 210,
    ];

    public function run(): void
    {
        $products = Product::where('status', 'active')->get();

        foreach ($products as $product) {
            // Skip if this product already has media records
            if ($product->media()->exists()) {
                continue;
            }

            $imageCount = rand(3, 5);
            $baseId     = $product->id;

            for ($i = 1; $i <= $imageCount; $i++) {
                // Pick a pseudo-random but deterministic photo for this product+index
                $photoId = $this->photoIds[($baseId * 7 + $i * 3) % count($this->photoIds)];

                $seed         = "prod{$baseId}img{$i}";
                $fileUrl      = "https://picsum.photos/seed/{$seed}/800/800";
                $thumbnailUrl = "https://picsum.photos/seed/{$seed}/300/300";

                ProductMedia::create([
                    'product_id'    => $product->id,
                    'variant_id'    => null,
                    'type'          => 'image',
                    'file_name'     => "product-{$baseId}-{$i}.jpg",
                    'file_path'     => "products/seeded/product-{$baseId}-{$i}.jpg",
                    'file_url'      => $fileUrl,
                    'thumbnail_url' => $thumbnailUrl,
                    'mime_type'     => 'image/jpeg',
                    'file_size'     => rand(280000, 620000),
                    'alt'           => $product->name . ' – view ' . $i,
                    'title'         => $product->name,
                    'sort_order'    => $i,
                    'is_primary'    => $i === 1,
                ]);
            }
        }
    }
}
