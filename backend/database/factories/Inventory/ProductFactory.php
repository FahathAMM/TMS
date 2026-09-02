<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name         = fake()->unique()->words(3, true);
        $costPrice    = fake()->randomFloat(2, 5, 500);
        $sellingPrice = round($costPrice * fake()->randomFloat(2, 1.2, 2.5), 2);

        return [
            'name'                => ucwords($name),
            'slug'                => Str::slug($name),
            'sku'                 => strtoupper(fake()->bothify('???-#####')),
            'barcode'             => fake()->optional()->ean13(),
            'type'                => ProductType::Simple,
            'status'              => ProductStatus::Active,
            'category_id'         => Category::factory(),
            'brand_id'            => fake()->optional(0.7)->passthrough(Brand::factory()),
            'description'         => fake()->paragraph(3),
            'short_description'   => fake()->sentence(12),
            'cost_price'          => $costPrice,
            'selling_price'       => $sellingPrice,
            'compare_price'       => fake()->optional(0.4)->passthrough(round($sellingPrice * 1.15, 2)),
            'stock_quantity'      => fake()->numberBetween(0, 200),
            'low_stock_threshold' => fake()->randomElement([3, 5, 10]),
            'weight'              => fake()->optional(0.8)->randomFloat(3, 0.05, 5),
            'weight_unit'         => 'kg',
            'is_active'           => true,
            'is_featured'         => fake()->boolean(15),
            'is_digital'          => false,
            'sort_order'          => 0,
            'published_at'        => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => ProductStatus::Draft, 'published_at' => null]);
    }

    public function inactive(): static
    {
        return $this->state(['status' => ProductStatus::Inactive, 'is_active' => false]);
    }

    public function featured(): static
    {
        return $this->state(['is_featured' => true]);
    }

    public function variable(): static
    {
        return $this->state(['type' => ProductType::Variable]);
    }

    public function digital(): static
    {
        return $this->state(['is_digital' => true, 'weight' => null]);
    }

    public function lowStock(): static
    {
        return $this->state([
            'stock_quantity'      => fake()->numberBetween(0, 3),
            'low_stock_threshold' => 5,
        ]);
    }
}
