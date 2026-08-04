<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Database\Seeder;

class ProductAttributeValuesSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedColors();
        $this->seedStorage();
        $this->seedSize();
        $this->seedMaterial();
    }

    private function seedColors(): void
    {
        $attr = Attribute::where('slug', 'color')->first();
        if (!$attr) return;

        // Extend the existing color palette with premium device shades
        $values = [
            ['value' => 'natural-titanium', 'label' => 'Natural Titanium', 'color_code' => '#C4BAA8', 'sort_order' => 11],
            ['value' => 'black-titanium',   'label' => 'Black Titanium',   'color_code' => '#3D3935', 'sort_order' => 12],
            ['value' => 'white-titanium',   'label' => 'White Titanium',   'color_code' => '#E8E4DF', 'sort_order' => 13],
            ['value' => 'desert-titanium',  'label' => 'Desert Titanium',  'color_code' => '#C4A882', 'sort_order' => 14],
            ['value' => 'midnight',         'label' => 'Midnight',         'color_code' => '#1B2437', 'sort_order' => 15],
            ['value' => 'starlight',        'label' => 'Starlight',        'color_code' => '#F3E8D7', 'sort_order' => 16],
            ['value' => 'space-gray',       'label' => 'Space Gray',       'color_code' => '#6E6E73', 'sort_order' => 17],
            ['value' => 'graphite',         'label' => 'Graphite',         'color_code' => '#41424C', 'sort_order' => 18],
            ['value' => 'yellow',           'label' => 'Yellow',           'color_code' => '#F4C430', 'sort_order' => 19],
            ['value' => 'pink',             'label' => 'Pink',             'color_code' => '#E78CAE', 'sort_order' => 20],
        ];

        foreach ($values as $v) {
            AttributeValue::firstOrCreate(
                ['attribute_id' => $attr->id, 'value' => $v['value']],
                array_merge($v, ['attribute_id' => $attr->id])
            );
        }
    }

    private function seedStorage(): void
    {
        $attr = Attribute::where('slug', 'storage')->first();
        if (!$attr) return;

        // 2 TB option for high-end tablets
        AttributeValue::firstOrCreate(
            ['attribute_id' => $attr->id, 'value' => '2tb'],
            ['attribute_id' => $attr->id, 'value' => '2tb', 'label' => '2 TB', 'sort_order' => 6]
        );
    }

    private function seedSize(): void
    {
        $attr = Attribute::where('slug', 'size')->first();
        if (!$attr) return;

        $values = [
            ['value' => 'one-size', 'label' => 'One Size', 'sort_order' => 1],
            ['value' => 'xs',       'label' => 'XS',       'sort_order' => 2],
            ['value' => 's',        'label' => 'S',        'sort_order' => 3],
            ['value' => 'm',        'label' => 'M',        'sort_order' => 4],
            ['value' => 'l',        'label' => 'L',        'sort_order' => 5],
            ['value' => 'xl',       'label' => 'XL',       'sort_order' => 6],
        ];

        foreach ($values as $v) {
            AttributeValue::firstOrCreate(
                ['attribute_id' => $attr->id, 'value' => $v['value']],
                array_merge($v, ['attribute_id' => $attr->id])
            );
        }
    }

    private function seedMaterial(): void
    {
        $attr = Attribute::where('slug', 'material')->first();
        if (!$attr) return;

        $values = [
            ['value' => 'silicone',       'label' => 'Silicone',       'sort_order' => 1],
            ['value' => 'leather',        'label' => 'Leather',        'sort_order' => 2],
            ['value' => 'tpu',            'label' => 'TPU',            'sort_order' => 3],
            ['value' => 'polycarbonate',  'label' => 'Polycarbonate',  'sort_order' => 4],
            ['value' => 'aluminum',       'label' => 'Aluminum',       'sort_order' => 5],
            ['value' => 'tempered-glass', 'label' => 'Tempered Glass', 'sort_order' => 6],
            ['value' => 'carbon-fiber',   'label' => 'Carbon Fiber',   'sort_order' => 7],
            ['value' => 'nylon',          'label' => 'Nylon',          'sort_order' => 8],
        ];

        foreach ($values as $v) {
            AttributeValue::firstOrCreate(
                ['attribute_id' => $attr->id, 'value' => $v['value']],
                array_merge($v, ['attribute_id' => $attr->id])
            );
        }
    }
}
