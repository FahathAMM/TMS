<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name'        => ucwords($name),
            'slug'        => Str::slug($name),
            'description' => fake()->optional()->sentence(8),
            'parent_id'   => null,
            'depth'       => 0,
            'path'        => '',
            'sort_order'  => 0,
            'is_active'   => true,
        ];
    }

    public function childOf(Category $parent): static
    {
        return $this->state([
            'parent_id'  => $parent->id,
            'depth'      => $parent->depth + 1,
            'path'       => ($parent->path ?? '') . $parent->id . '/',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
