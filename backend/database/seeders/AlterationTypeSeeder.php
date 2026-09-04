<?php

namespace Database\Seeders;

use App\Models\Tailoring\AlterationType;
use Illuminate\Database\Seeder;

class AlterationTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Hem Trousers',            'price' => 8.00],
            ['name' => 'Hem Skirt/Dress',          'price' => 10.00],
            ['name' => 'Take In Waist',            'price' => 12.00],
            ['name' => 'Let Out Waist',            'price' => 12.00],
            ['name' => 'Take In Sides (Shirt/Dress)', 'price' => 14.00],
            ['name' => 'Shorten Sleeves',          'price' => 10.00],
            ['name' => 'Lengthen Sleeves',         'price' => 12.00],
            ['name' => 'Adjust Shoulders',         'price' => 18.00],
            ['name' => 'Replace Zipper',           'price' => 12.00],
            ['name' => 'Patch Repair',             'price' => 8.00],
            ['name' => 'Resize Jacket',            'price' => 25.00],
            ['name' => 'Add/Move Buttons',         'price' => 5.00],
        ];

        foreach ($types as $type) {
            AlterationType::firstOrCreate(['name' => $type['name']], $type);
        }
    }
}
