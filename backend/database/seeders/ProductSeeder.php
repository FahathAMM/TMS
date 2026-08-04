<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $categoryIds = Category::pluck('id')->toArray();
        $brandIds    = Brand::pluck('id')->toArray();

        $templates = [
            // Smartphones
            ['iPhone 15 Pro Max', 1, 1, 950, 1199], ['iPhone 15 Plus', 1, 1, 820, 999],
            ['iPhone 14 Pro', 1, 1, 750, 929],      ['iPhone 14', 1, 1, 620, 799],
            ['iPhone 13', 1, 1, 490, 649],           ['iPhone SE 3rd Gen', 1, 1, 380, 499],
            ['Samsung Galaxy S24 Ultra', 1, 2, 900, 1249], ['Samsung Galaxy S24+', 1, 2, 780, 999],
            ['Samsung Galaxy S24', 1, 2, 680, 849],  ['Samsung Galaxy A55', 1, 2, 320, 449],
            ['Samsung Galaxy A35', 1, 2, 240, 329],  ['Samsung Galaxy A15', 1, 2, 140, 199],
            ['Samsung Galaxy Z Fold 6', 1, 2, 1400, 1799], ['Samsung Galaxy Z Flip 6', 1, 2, 880, 1099],
            ['Xiaomi 14 Pro', 1, 3, 580, 749],       ['Xiaomi 14', 1, 3, 450, 599],
            ['Xiaomi Redmi Note 13 Pro', 1, 3, 200, 279], ['Xiaomi Redmi Note 13', 1, 3, 140, 199],
            ['Xiaomi POCO X6 Pro', 1, 3, 260, 349],  ['OnePlus 12', 1, 4, 580, 749],
            ['OnePlus 12R', 1, 4, 380, 499],         ['OnePlus Nord 4', 1, 4, 300, 399],
            ['Oppo Reno 12 Pro', 1, 5, 420, 549],    ['Oppo Find X7', 1, 5, 680, 879],
            ['Oppo A3 Pro', 1, 5, 200, 269],

            // Tablets
            ['iPad Pro 13" M4', 3, 1, 900, 1199],   ['iPad Pro 11" M4', 3, 1, 720, 999],
            ['iPad Air 13" M2', 3, 1, 680, 899],     ['iPad Air 11" M2', 3, 1, 520, 699],
            ['iPad mini 7', 3, 1, 420, 549],          ['iPad 10th Gen', 3, 1, 340, 449],
            ['Samsung Galaxy Tab S10 Ultra', 3, 2, 840, 1099], ['Samsung Galaxy Tab S10+', 3, 2, 680, 899],
            ['Samsung Galaxy Tab S10', 3, 2, 540, 699], ['Samsung Galaxy Tab A9+', 3, 2, 260, 349],

            // Earphones
            ['AirPods Pro 2nd Gen', 4, 1, 180, 249], ['AirPods 4 ANC', 4, 1, 150, 199],
            ['AirPods 4', 4, 1, 110, 149],           ['AirPods Max 2', 4, 1, 440, 549],
            ['Samsung Galaxy Buds3 Pro', 4, 2, 140, 199], ['Samsung Galaxy Buds3', 4, 2, 100, 139],
            ['Galaxy Buds FE', 4, 2, 70, 99],        ['OnePlus Buds 3', 4, 4, 80, 109],
            ['Xiaomi Buds 5 Pro', 4, 3, 90, 129],

            // Accessories
            ['MagSafe Leather Case iPhone 15', 2, 1, 25, 49],
            ['Clear Case iPhone 15 Pro', 2, 1, 12, 29],
            ['Silicone Case iPhone 14 Pro', 2, 1, 18, 39],
            ['Rugged Case Galaxy S24', 2, 2, 15, 35],
            ['Flip Cover Galaxy S24 Ultra', 2, 2, 20, 45],
            ['Tempered Glass iPhone 15', 2, null, 5, 14.99],
            ['Privacy Screen Protector iPhone 15 Pro', 2, null, 8, 19.99],
            ['Anti-Glare Film Galaxy S24', 2, null, 6, 14.99],
            ['Universal Phone Ring Holder', 2, null, 3, 9.99],
            ['Magnetic Phone Grip', 2, null, 4, 12.99],
            ['MagSafe Pop Socket', 2, 1, 12, 29.99],
            ['Waterproof Phone Pouch', 2, null, 6, 16.99],

            // Chargers
            ['Apple 30W USB-C Charger', 5, 1, 28, 49],
            ['Apple 20W USB-C Charger', 5, 1, 18, 29],
            ['Apple MagSafe Charger 15W', 5, 1, 22, 39],
            ['Apple MagSafe Duo Charger', 5, 1, 65, 99],
            ['Samsung 45W Super Fast Charger', 5, 2, 30, 49],
            ['Samsung 25W Fast Charger', 5, 2, 20, 35],
            ['Samsung Wireless Charger Pad 15W', 5, 2, 25, 45],
            ['Samsung 3-in-1 Wireless Charger', 5, 2, 55, 89],
            ['Xiaomi 120W HyperCharge Adapter', 5, 3, 18, 34.99],
            ['Anker 65W GaN USB-C Charger', 5, null, 28, 49.99],
            ['Anker 100W 4-Port GaN Charger', 5, null, 45, 79.99],
            ['Belkin BoostCharge Pro 3-in-1', 5, null, 55, 89.99],
            ['USB-C to USB-C Cable 2m', 5, null, 4, 12.99],
            ['USB-C to Lightning Cable 1m', 5, null, 6, 16.99],
            ['MagSafe Compatible Cable Clip', 5, null, 3, 8.99],
            ['20W Fast Charger Universal', 5, null, 8, 19.99],
            ['10000mAh Power Bank USB-C', 5, null, 18, 39.99],
            ['20000mAh Power Bank PD 65W', 5, null, 32, 59.99],
            ['MagSafe Power Bank 10000mAh', 5, 1, 40, 69.99],
            ['Car Wireless Charger 15W', 5, null, 14, 29.99],
            ['Dual USB-C Car Charger 40W', 5, null, 9, 19.99],
        ];

        // Build a map of category/brand index → actual IDs
        // The seeder seeds categories in order: Smartphones=1, Accessories=2, Tablets=3, Earphones=4, Chargers=5
        // Map template category_id (1-5) to actual DB IDs
        $catMap   = array_values($categoryIds);   // index 0 = first seeded category
        $brandMap = array_values($brandIds);       // index 0 = Apple (1st brand)

        // brand index in templates: 1=Apple,2=Samsung,3=Xiaomi,4=OnePlus,5=Oppo, null=no brand
        $brandIndex = [1 => 0, 2 => 1, 3 => 2, 4 => 3, 5 => 4];

        foreach ($templates as [$name, $catIdx, $brandIdx, $cost, $sell]) {
            $resolvedCat   = $catMap[$catIdx - 1] ?? $faker->randomElement($categoryIds);
            $resolvedBrand = ($brandIdx !== null && isset($brandIndex[$brandIdx]))
                ? ($brandMap[$brandIndex[$brandIdx]] ?? null)
                : null;

            $sku = strtoupper(
                substr(preg_replace('/[^A-Z0-9]/', '', strtoupper($name)), 0, 6)
                . '-' . $faker->unique()->numerify('#####')
            );

            Product::firstOrCreate(
                ['name' => $name],
                [
                    'sku'                 => $sku,
                    'category_id'         => $resolvedCat,
                    'brand_id'            => $resolvedBrand,
                    'cost_price'          => $cost,
                    'selling_price'       => $sell,
                    'stock_quantity'      => $faker->numberBetween(0, 80),
                    'low_stock_threshold' => $faker->randomElement([3, 5, 10]),
                    'description'         => $faker->sentence(10),
                    'is_active'           => true,
                    'status'              => 'active',
                ]
            );
        }

        // Fill up to 100 products with extra random accessories
        $extras = [
            'Phone Case', 'Screen Protector', 'USB-C Cable', 'Type-C Hub',
            'Wireless Charger Pad', 'Car Charger', 'Phone Stand', 'LED Case',
            'Magnetic Wallet', 'Pop Socket', 'Cable Clip', 'Dust Plug Set',
            'Ring Light Selfie', 'Gimbal Phone Mount', 'Phone Lens Kit',
        ];
        $brands  = ['(Apple)', '(Samsung)', '(Xiaomi)', '(Generic)', '(Anker)', '(Belkin)', '(Baseus)'];
        $targets = ['iPhone 15', 'iPhone 14', 'Galaxy S24', 'Galaxy A55', 'Redmi Note 13', 'Universal'];

        $needed = max(0, 100 - Product::count());
        for ($i = 0; $i < $needed; $i++) {
            $name = $faker->randomElement($extras)
                . ' ' . $faker->randomElement($targets)
                . ' ' . $faker->randomElement($brands);
            $cost = $faker->randomFloat(2, 3, 60);
            $sku  = strtoupper($faker->bothify('???-#####'));

            Product::firstOrCreate(
                ['name' => $name],
                [
                    'sku'                 => $sku,
                    'category_id'         => $faker->randomElement($categoryIds),
                    'brand_id'            => $faker->optional(0.6)->randomElement($brandIds),
                    'cost_price'          => $cost,
                    'selling_price'       => round($cost * $faker->randomFloat(2, 1.3, 2.5), 2),
                    'stock_quantity'      => $faker->numberBetween(0, 60),
                    'low_stock_threshold' => $faker->randomElement([3, 5, 10]),
                    'description'         => $faker->sentence(8),
                    'is_active'           => true,
                    'status'              => 'active',
                ]
            );
        }
    }
}
