<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AttributeSeeder extends Seeder
{
    public function run(): void
    {
        $attributes = [
            [
                'name'          => 'Color',
                'type'          => 'color',
                'is_filterable' => true,
                'sort_order'    => 1,
                'values'        => [
                    ['label' => 'Black',     'value' => 'black',     'color_code' => '#000000', 'sort_order' => 1],
                    ['label' => 'White',     'value' => 'white',     'color_code' => '#FFFFFF', 'sort_order' => 2],
                    ['label' => 'Silver',    'value' => 'silver',    'color_code' => '#C0C0C0', 'sort_order' => 3],
                    ['label' => 'Gold',      'value' => 'gold',      'color_code' => '#FFD700', 'sort_order' => 4],
                    ['label' => 'Blue',      'value' => 'blue',      'color_code' => '#0000FF', 'sort_order' => 5],
                    ['label' => 'Red',       'value' => 'red',       'color_code' => '#FF0000', 'sort_order' => 6],
                    ['label' => 'Green',     'value' => 'green',     'color_code' => '#008000', 'sort_order' => 7],
                    ['label' => 'Purple',    'value' => 'purple',    'color_code' => '#800080', 'sort_order' => 8],
                    ['label' => 'Titanium',  'value' => 'titanium',  'color_code' => '#878681', 'sort_order' => 9],
                    ['label' => 'Rose Gold', 'value' => 'rose-gold', 'color_code' => '#B76E79', 'sort_order' => 10],
                ],
            ],
            [
                'name'          => 'Storage',
                'type'          => 'select',
                'is_filterable' => true,
                'sort_order'    => 2,
                'values'        => [
                    ['label' => '64 GB',   'value' => '64gb',   'sort_order' => 1],
                    ['label' => '128 GB',  'value' => '128gb',  'sort_order' => 2],
                    ['label' => '256 GB',  'value' => '256gb',  'sort_order' => 3],
                    ['label' => '512 GB',  'value' => '512gb',  'sort_order' => 4],
                    ['label' => '1 TB',    'value' => '1tb',    'sort_order' => 5],
                ],
            ],
            [
                'name'          => 'RAM',
                'type'          => 'select',
                'is_filterable' => true,
                'sort_order'    => 3,
                'values'        => [
                    ['label' => '4 GB',  'value' => '4gb',  'sort_order' => 1],
                    ['label' => '6 GB',  'value' => '6gb',  'sort_order' => 2],
                    ['label' => '8 GB',  'value' => '8gb',  'sort_order' => 3],
                    ['label' => '12 GB', 'value' => '12gb', 'sort_order' => 4],
                    ['label' => '16 GB', 'value' => '16gb', 'sort_order' => 5],
                ],
            ],
            [
                'name'          => 'Network',
                'type'          => 'select',
                'is_filterable' => true,
                'sort_order'    => 4,
                'values'        => [
                    ['label' => '4G LTE', 'value' => '4g',   'sort_order' => 1],
                    ['label' => '5G',     'value' => '5g',   'sort_order' => 2],
                    ['label' => 'Wi-Fi',  'value' => 'wifi', 'sort_order' => 3],
                ],
            ],
            [
                'name'          => 'Connectivity',
                'type'          => 'select',
                'is_filterable' => true,
                'sort_order'    => 5,
                'values'        => [
                    ['label' => 'USB-C',      'value' => 'usb-c',      'sort_order' => 1],
                    ['label' => 'Lightning',  'value' => 'lightning',  'sort_order' => 2],
                    ['label' => 'Micro-USB',  'value' => 'micro-usb',  'sort_order' => 3],
                    ['label' => 'MagSafe',    'value' => 'magsafe',    'sort_order' => 4],
                ],
            ],
            [
                'name'          => 'Screen Size',
                'type'          => 'select',
                'is_filterable' => true,
                'sort_order'    => 6,
                'values'        => [
                    ['label' => '5.4"',  'value' => '5.4',  'sort_order' => 1],
                    ['label' => '6.1"',  'value' => '6.1',  'sort_order' => 2],
                    ['label' => '6.7"',  'value' => '6.7',  'sort_order' => 3],
                    ['label' => '6.8"',  'value' => '6.8',  'sort_order' => 4],
                    ['label' => '10.9"', 'value' => '10.9', 'sort_order' => 5],
                    ['label' => '11"',   'value' => '11',   'sort_order' => 6],
                    ['label' => '13"',   'value' => '13',   'sort_order' => 7],
                ],
            ],
            [
                'name'          => 'Wattage',
                'type'          => 'select',
                'is_filterable' => false,
                'sort_order'    => 7,
                'values'        => [
                    ['label' => '5W',   'value' => '5w',   'sort_order' => 1],
                    ['label' => '15W',  'value' => '15w',  'sort_order' => 2],
                    ['label' => '20W',  'value' => '20w',  'sort_order' => 3],
                    ['label' => '25W',  'value' => '25w',  'sort_order' => 4],
                    ['label' => '45W',  'value' => '45w',  'sort_order' => 5],
                    ['label' => '65W',  'value' => '65w',  'sort_order' => 6],
                    ['label' => '100W', 'value' => '100w', 'sort_order' => 7],
                    ['label' => '120W', 'value' => '120w', 'sort_order' => 8],
                ],
            ],
        ];

        foreach ($attributes as $attributeData) {
            $values = $attributeData['values'] ?? [];
            unset($attributeData['values']);

            $attributeData['slug'] = Str::slug($attributeData['name']);

            $attribute = Attribute::firstOrCreate(
                ['slug' => $attributeData['slug']],
                $attributeData
            );

            foreach ($values as $valueData) {
                AttributeValue::firstOrCreate(
                    ['attribute_id' => $attribute->id, 'value' => $valueData['value']],
                    array_merge($valueData, ['attribute_id' => $attribute->id])
                );
            }
        }
    }
}
