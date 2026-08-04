<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

class ProductVariantsSeeder extends Seeder
{
    private array $colorAbbr = [
        'black'            => 'BK',
        'white'            => 'WH',
        'silver'           => 'SV',
        'gold'             => 'GD',
        'blue'             => 'BL',
        'red'              => 'RD',
        'green'            => 'GN',
        'purple'           => 'PU',
        'titanium'         => 'TI',
        'rose-gold'        => 'RG',
        'natural-titanium' => 'NT',
        'black-titanium'   => 'BT',
        'white-titanium'   => 'WT',
        'desert-titanium'  => 'DT',
        'midnight'         => 'MN',
        'starlight'        => 'SL',
        'space-gray'       => 'SG',
        'graphite'         => 'GP',
        'yellow'           => 'YL',
        'pink'             => 'PK',
    ];

    private array $storageAbbr = [
        '64gb'  => '64G',
        '128gb' => '128',
        '256gb' => '256',
        '512gb' => '512',
        '1tb'   => '1TB',
        '2tb'   => '2TB',
    ];

    public function run(): void
    {
        $colorAttr   = Attribute::where('slug', 'color')->first();
        $storageAttr = Attribute::where('slug', 'storage')->first();

        if (!$colorAttr || !$storageAttr) {
            $this->command->warn('Color or Storage attribute missing — run AttributeSeeder first.');
            return;
        }

        $colorVals   = AttributeValue::where('attribute_id', $colorAttr->id)->pluck('id', 'value');
        $storageVals = AttributeValue::where('attribute_id', $storageAttr->id)->pluck('id', 'value');

        // Format: [productName => config]
        // storagePrices: storage => [price, cost]
        // colorPrice: [price, cost]  (when no storage dimension)
        $configs = [
            'iPhone 15 Pro Max' => [
                'skuPrefix'    => 'IP15PM',
                'colors'       => ['natural-titanium', 'black-titanium', 'white-titanium', 'desert-titanium'],
                'storages'     => ['256gb', '512gb', '1tb'],
                'storagePrices' => [
                    '256gb' => [1199.00, 850.00],
                    '512gb' => [1299.00, 920.00],
                    '1tb'   => [1499.00, 1000.00],
                ],
                'stock' => [3, 25],
            ],
            'iPhone 15 Plus' => [
                'skuPrefix'    => 'IP15PL',
                'colors'       => ['black', 'blue', 'yellow', 'pink'],
                'storages'     => ['128gb', '256gb', '512gb'],
                'storagePrices' => [
                    '128gb' => [899.00,  650.00],
                    '256gb' => [999.00,  710.00],
                    '512gb' => [1099.00, 760.00],
                ],
                'stock' => [5, 30],
            ],
            'Samsung Galaxy S24 Ultra' => [
                'skuPrefix'    => 'SGS24U',
                'colors'       => ['black-titanium', 'graphite', 'purple', 'yellow'],
                'storages'     => ['256gb', '512gb', '1tb'],
                'storagePrices' => [
                    '256gb' => [1299.00, 900.00],
                    '512gb' => [1399.00, 980.00],
                    '1tb'   => [1599.00, 1080.00],
                ],
                'stock' => [3, 20],
            ],
            'Samsung Galaxy S24' => [
                'skuPrefix'    => 'SGS24',
                'colors'       => ['black', 'silver', 'blue', 'green'],
                'storages'     => ['128gb', '256gb'],
                'storagePrices' => [
                    '128gb' => [849.00, 600.00],
                    '256gb' => [899.00, 640.00],
                ],
                'stock' => [5, 35],
            ],
            'Xiaomi 14 Pro' => [
                'skuPrefix'    => 'XM14P',
                'colors'       => ['black', 'white', 'silver'],
                'storages'     => ['256gb', '512gb'],
                'storagePrices' => [
                    '256gb' => [749.00, 520.00],
                    '512gb' => [849.00, 580.00],
                ],
                'stock' => [5, 30],
            ],
            'OnePlus 12' => [
                'skuPrefix'    => 'OP12',
                'colors'       => ['green', 'black'],
                'storages'     => ['256gb', '512gb'],
                'storagePrices' => [
                    '256gb' => [749.00, 500.00],
                    '512gb' => [849.00, 560.00],
                ],
                'stock' => [5, 25],
            ],
            'iPad Pro 13" M4' => [
                'skuPrefix'    => 'IPPM4',
                'colors'       => ['space-gray', 'silver'],
                'storages'     => ['256gb', '512gb', '1tb'],
                'storagePrices' => [
                    '256gb' => [1299.00, 900.00],
                    '512gb' => [1499.00, 1000.00],
                    '1tb'   => [1699.00, 1150.00],
                ],
                'stock' => [2, 15],
            ],
            'iPad Air 11" M2' => [
                'skuPrefix'    => 'IPAM2',
                'colors'       => ['blue', 'purple', 'starlight', 'space-gray'],
                'storages'     => ['128gb', '256gb', '512gb'],
                'storagePrices' => [
                    '128gb' => [699.00,  480.00],
                    '256gb' => [849.00,  570.00],
                    '512gb' => [999.00,  650.00],
                ],
                'stock' => [3, 20],
            ],
            'Samsung Galaxy Buds3 Pro' => [
                'skuPrefix'  => 'SGBU3P',
                'colors'     => ['silver', 'graphite'],
                'colorPrice' => [199.00, 130.00],
                'stock'      => [5, 40],
            ],
            'MagSafe Leather Case iPhone 15' => [
                'skuPrefix'  => 'MSFLC15',
                'colors'     => ['midnight', 'black', 'green', 'starlight', 'gold'],
                'colorPrice' => [59.00, 18.00],
                'stock'      => [10, 80],
            ],
            'Silicone Case iPhone 15' => [
                'skuPrefix'  => 'SCIP15',
                'colors'     => ['midnight', 'blue', 'green', 'pink', 'white'],
                'colorPrice' => [49.00, 12.00],
                'stock'      => [10, 100],
            ],
        ];

        foreach ($configs as $productName => $cfg) {
            $product = Product::where('name', $productName)->first();
            if (!$product) {
                continue;
            }

            $usedAttributeIds = [];
            $sortOrder = 0;

            if (!empty($cfg['storages'])) {
                // Color × Storage grid
                foreach ($cfg['colors'] as $colorVal) {
                    foreach ($cfg['storages'] as $storageVal) {
                        [$price, $cost] = $cfg['storagePrices'][$storageVal];
                        $colorCode   = $this->colorAbbr[$colorVal]   ?? strtoupper(substr($colorVal, 0, 2));
                        $storageCode = $this->storageAbbr[$storageVal] ?? strtoupper($storageVal);
                        $sku = "{$cfg['skuPrefix']}-{$colorCode}-{$storageCode}";

                        $attrs = [
                            'product_id'          => $product->id,
                            'price'               => $price,
                            'cost_price'          => $cost,
                            'stock_quantity'      => rand($cfg['stock'][0], $cfg['stock'][1]),
                            'low_stock_threshold' => $cfg['stock'][0],
                            'is_active'           => true,
                            'sort_order'          => $sortOrder++,
                        ];
                        $variant = $this->findOrCreateVariant($sku, $attrs);

                        $avIds = array_filter([
                            $colorVals[$colorVal]   ?? null,
                            $storageVals[$storageVal] ?? null,
                        ]);
                        $variant->attributeValues()->sync($avIds);

                        $usedAttributeIds[] = $colorAttr->id;
                        $usedAttributeIds[] = $storageAttr->id;
                    }
                }
            } else {
                // Color only
                [$price, $cost] = $cfg['colorPrice'];

                foreach ($cfg['colors'] as $colorVal) {
                    $colorCode = $this->colorAbbr[$colorVal] ?? strtoupper(substr($colorVal, 0, 2));
                    $sku = "{$cfg['skuPrefix']}-{$colorCode}";

                    $attrs = [
                        'product_id'          => $product->id,
                        'price'               => $price,
                        'cost_price'          => $cost,
                        'stock_quantity'      => rand($cfg['stock'][0], $cfg['stock'][1]),
                        'low_stock_threshold' => $cfg['stock'][0],
                        'is_active'           => true,
                        'sort_order'          => $sortOrder++,
                    ];
                    $variant = $this->findOrCreateVariant($sku, $attrs);

                    $avId = $colorVals[$colorVal] ?? null;
                    if ($avId) {
                        $variant->attributeValues()->sync([$avId]);
                    }

                    $usedAttributeIds[] = $colorAttr->id;
                }
            }

            // Link the used attributes to the product
            $product->attributes()->sync(array_unique($usedAttributeIds));
        }
    }

    private function findOrCreateVariant(string $sku, array $attrs): ProductVariant
    {
        // withTrashed so soft-deleted records don't ghost the unique constraint
        $variant = ProductVariant::withTrashed()->where('sku', $sku)->first();

        if ($variant) {
            if ($variant->trashed()) {
                $variant->restore();
            }
            return $variant;
        }

        return ProductVariant::create(array_merge(['sku' => $sku], $attrs));
    }
}
