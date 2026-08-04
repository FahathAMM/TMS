<?php

namespace Database\Seeders;

use App\Models\MeasurementType;
use Illuminate\Database\Seeder;

class MeasurementTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            // Shirt
            ['name' => 'Chest',         'category' => 'Shirt', 'unit' => 'inches'],
            ['name' => 'Shoulder',      'category' => 'Shirt', 'unit' => 'inches'],
            ['name' => 'Sleeve Length', 'category' => 'Shirt', 'unit' => 'inches'],
            ['name' => 'Collar',        'category' => 'Shirt', 'unit' => 'inches'],
            ['name' => 'Shirt Length',  'category' => 'Shirt', 'unit' => 'inches'],
            // Suit Jacket
            ['name' => 'Chest',         'category' => 'Suit Jacket', 'unit' => 'inches'],
            ['name' => 'Waist',         'category' => 'Suit Jacket', 'unit' => 'inches'],
            ['name' => 'Shoulder',      'category' => 'Suit Jacket', 'unit' => 'inches'],
            ['name' => 'Sleeve Length', 'category' => 'Suit Jacket', 'unit' => 'inches'],
            ['name' => 'Jacket Length', 'category' => 'Suit Jacket', 'unit' => 'inches'],
            // Trousers
            ['name' => 'Waist',   'category' => 'Trousers', 'unit' => 'inches'],
            ['name' => 'Hip',     'category' => 'Trousers', 'unit' => 'inches'],
            ['name' => 'Inseam',  'category' => 'Trousers', 'unit' => 'inches'],
            ['name' => 'Outseam', 'category' => 'Trousers', 'unit' => 'inches'],
            ['name' => 'Thigh',   'category' => 'Trousers', 'unit' => 'inches'],
            // Dress
            ['name' => 'Bust',          'category' => 'Dress', 'unit' => 'inches'],
            ['name' => 'Waist',         'category' => 'Dress', 'unit' => 'inches'],
            ['name' => 'Hip',           'category' => 'Dress', 'unit' => 'inches'],
            ['name' => 'Dress Length',  'category' => 'Dress', 'unit' => 'inches'],
            ['name' => 'Sleeve Length', 'category' => 'Dress', 'unit' => 'inches'],
        ];

        foreach ($types as $type) {
            MeasurementType::firstOrCreate(
                ['name' => $type['name'], 'category' => $type['category']],
                $type
            );
        }
    }
}
