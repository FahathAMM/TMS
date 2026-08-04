<?php

namespace Database\Seeders;

use App\Models\Attribute;
use Illuminate\Database\Seeder;

class ProductAttributesSeeder extends Seeder
{
    public function run(): void
    {
        // Color, Storage, Connectivity are already seeded by AttributeSeeder.
        // This seeder only adds the attributes that aren't there yet.
        $attributes = [
            [
                'name'          => 'Material',
                'slug'          => 'material',
                'type'          => 'select',
                'is_required'   => false,
                'is_filterable' => false,
                'sort_order'    => 8,
            ],
            [
                'name'          => 'Size',
                'slug'          => 'size',
                'type'          => 'size',
                'is_required'   => false,
                'is_filterable' => true,
                'sort_order'    => 9,
            ],
        ];

        foreach ($attributes as $attr) {
            Attribute::firstOrCreate(['slug' => $attr['slug']], $attr);
        }
    }
}
