<?php

namespace Database\Factories;

use App\Enums\AttributeType;
use App\Models\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AttributeFactory extends Factory
{
    protected $model = Attribute::class;

    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name'          => ucfirst($name),
            'slug'          => Str::slug($name),
            'type'          => AttributeType::Select,
            'is_required'   => false,
            'is_filterable' => true,
            'sort_order'    => 0,
        ];
    }

    public function color(): static
    {
        return $this->state(['type' => AttributeType::Color, 'name' => 'Color', 'slug' => 'color']);
    }

    public function size(): static
    {
        return $this->state(['type' => AttributeType::Size, 'name' => 'Size', 'slug' => 'size']);
    }
}
