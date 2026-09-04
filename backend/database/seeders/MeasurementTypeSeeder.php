<?php

namespace Database\Seeders;

use App\Models\Tailoring\MeasurementType;
use App\Services\MeasurementTypeService;
use Illuminate\Database\Seeder;

class MeasurementTypeSeeder extends Seeder
{
    public function run(MeasurementTypeService $service): void
    {
        $types = [
            [
                'name'        => 'Thobe',
                'description' => 'Traditional full-length garment measurements',
                'fields'      => [
                    ['number' => 1, 'name' => 'Shoulder', 'unit' => 'inches', 'required' => true],
                    ['number' => 2, 'name' => 'Chest',    'unit' => 'inches', 'required' => true],
                    ['number' => 3, 'name' => 'Sleeve',   'unit' => 'inches', 'required' => true],
                    ['number' => 4, 'name' => 'Length',   'unit' => 'inches', 'required' => true],
                    ['number' => 5, 'name' => 'Waist',    'unit' => 'inches', 'required' => false],
                ],
            ],
            [
                'name'        => 'Trouser',
                'description' => 'Standard trouser measurements',
                'fields'      => [
                    ['number' => 1, 'name' => 'Waist',  'unit' => 'inches', 'required' => true],
                    ['number' => 2, 'name' => 'Hip',    'unit' => 'inches', 'required' => true],
                    ['number' => 3, 'name' => 'Length', 'unit' => 'inches', 'required' => true],
                    ['number' => 4, 'name' => 'Thigh',  'unit' => 'inches', 'required' => true],
                    ['number' => 5, 'name' => 'Bottom', 'unit' => 'inches', 'required' => false],
                ],
            ],
            [
                'name'        => 'Shirt',
                'description' => 'Standard dress/casual shirt measurements',
                'fields'      => [
                    ['number' => 1, 'name' => 'Shoulder',      'unit' => 'inches', 'required' => true],
                    ['number' => 2, 'name' => 'Chest',         'unit' => 'inches', 'required' => true],
                    ['number' => 3, 'name' => 'Sleeve Length', 'unit' => 'inches', 'required' => true],
                    ['number' => 4, 'name' => 'Collar',        'unit' => 'inches', 'required' => true],
                    ['number' => 5, 'name' => 'Shirt Length',  'unit' => 'inches', 'required' => true],
                ],
            ],
            [
                'name'        => 'Suit Jacket',
                'description' => 'Tailored suit jacket measurements',
                'fields'      => [
                    ['number' => 1, 'name' => 'Chest',         'unit' => 'inches', 'required' => true],
                    ['number' => 2, 'name' => 'Waist',         'unit' => 'inches', 'required' => true],
                    ['number' => 3, 'name' => 'Shoulder',      'unit' => 'inches', 'required' => true],
                    ['number' => 4, 'name' => 'Sleeve Length', 'unit' => 'inches', 'required' => true],
                    ['number' => 5, 'name' => 'Jacket Length', 'unit' => 'inches', 'required' => true],
                ],
            ],
            [
                'name'        => 'Dress',
                'description' => 'Standard dress measurements',
                'fields'      => [
                    ['number' => 1, 'name' => 'Bust',         'unit' => 'inches', 'required' => true],
                    ['number' => 2, 'name' => 'Waist',        'unit' => 'inches', 'required' => true],
                    ['number' => 3, 'name' => 'Hip',          'unit' => 'inches', 'required' => true],
                    ['number' => 4, 'name' => 'Dress Length', 'unit' => 'inches', 'required' => true],
                    ['number' => 5, 'name' => 'Sleeve Length', 'unit' => 'inches', 'required' => false],
                ],
            ],
        ];

        foreach ($types as $type) {
            if (MeasurementType::where('name', $type['name'])->exists()) {
                continue;
            }
            $service->create($type);
        }
    }
}
