<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ProductSeo;
use App\Models\ProductSpecification;
use App\Models\ProductVariant;
use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FreshProductSeeder extends Seeder
{
    // Pre-loaded lookups — set in loadLookups()
    private array $colorIds   = [];  // 'black' => id
    private array $storageIds = [];  // '128gb' => id
    private array $ramIds     = [];  // '8gb'   => id
    private array $cats       = [];  // 'Smartphones' => id
    private array $brands     = [];  // 'Apple' => id
    private array $tags       = [];  // 'iphone' => id

    // ── Entry point ───────────────────────────────────────────────────────────

    public function run(): void
    {
        $this->command->info('Truncating product tables…');
        $this->truncate();

        $this->command->info('Ensuring extra brands exist…');
        $this->ensureBrands();

        $this->command->info('Loading attribute lookups…');
        $this->loadLookups();

        $this->command->info('Seeding 20 products…');
        foreach ($this->catalog() as $config) {
            $this->seedProduct($config);
        }

        $this->command->info('Done — ' . Product::count() . ' products seeded.');
    }

    // ── Truncation ────────────────────────────────────────────────────────────

    private function truncate(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $tables = [
            'product_variant_values',
            'product_variants',
            'product_media',
            'product_seo',
            'product_specifications',
            'product_attributes',
            'product_tag',
            'offer_products',
            'stock_movements',
            'stock_adjustments',
            'sale_items',
            'products',
        ];

        foreach ($tables as $t) {
            DB::table($t)->truncate();
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    // ── Ensure extra brands ───────────────────────────────────────────────────

    private function ensureBrands(): void
    {
        foreach (['Google', 'Sony', 'Realme', 'Anker', 'Belkin', 'Baseus'] as $name) {
            Brand::firstOrCreate(['name' => $name]);
        }
    }

    // ── Lookup tables ─────────────────────────────────────────────────────────

    private function loadLookups(): void
    {
        $colorAttr   = Attribute::where('slug', 'color')->first();
        $storageAttr = Attribute::where('slug', 'storage')->first();
        $ramAttr     = Attribute::where('slug', 'ram')->first();

        if ($colorAttr) {
            $this->colorIds = AttributeValue::where('attribute_id', $colorAttr->id)
                ->pluck('id', 'value')->toArray();
        }
        if ($storageAttr) {
            $this->storageIds = AttributeValue::where('attribute_id', $storageAttr->id)
                ->pluck('id', 'value')->toArray();
        }
        if ($ramAttr) {
            $this->ramIds = AttributeValue::where('attribute_id', $ramAttr->id)
                ->pluck('id', 'value')->toArray();
        }

        $this->cats   = Category::pluck('id', 'name')->toArray();
        $this->brands = Brand::pluck('id', 'name')->toArray();
        $this->tags   = Tag::pluck('id', 'slug')->toArray();
    }

    // ── Product factory ───────────────────────────────────────────────────────

    private function seedProduct(array $cfg): void
    {
        $isVariable = isset($cfg['variants']);

        $product = Product::create([
            'sku'                 => $cfg['sku'],
            'name'                => $cfg['name'],
            'slug'                => Str::slug($cfg['name']),
            'type'                => $isVariable ? 'variable' : 'simple',
            'status'              => 'active',
            'category_id'         => $this->cats[$cfg['category']] ?? 1,
            'brand_id'            => $this->brands[$cfg['brand']] ?? null,
            'description'         => $cfg['description'],
            'short_description'   => $cfg['short_description'],
            'cost_price'          => $cfg['cost_price'],
            'selling_price'       => $cfg['selling_price'],
            'compare_price'       => $cfg['compare_price'] ?? null,
            'stock_quantity'      => $isVariable ? 0 : ($cfg['stock'] ?? 50),
            'low_stock_threshold' => $cfg['low_stock_threshold'] ?? 5,
            'weight'              => $cfg['weight'] ?? null,
            'weight_unit'         => 'kg',
            'is_featured'         => $cfg['is_featured'] ?? false,
            'is_best_seller'      => $cfg['is_best_seller'] ?? false,
            'is_new_arrival'      => $cfg['is_new_arrival'] ?? false,
            'is_active'           => true,
            'sort_order'          => 0,
            'published_at'        => now()->subDays(rand(1, 60)),
        ]);

        $this->attachTags($product, $cfg['tags'] ?? []);
        $this->createMedia($product, $cfg['sku'], $cfg['img'] ?? 'smartphone,technology');
        $this->createSeo($product, $cfg['seo'] ?? []);
        $this->createSpecs($product, $cfg['specs'] ?? []);

        if ($isVariable) {
            $totalStock = $this->createVariants($product, $cfg['variants']);
            $product->update(['stock_quantity' => $totalStock]);
        }
    }

    // ── Variant builder ───────────────────────────────────────────────────────

    /**
     * Supports three variant shapes:
     *   color_storage – every color × storage combination
     *   color_only    – one variant per color, flat price
     *   storage_only  – one variant per storage
     */
    private const COLOR_ABR = [
        'black'     => 'BK', 'white'    => 'WH', 'silver'   => 'SL',
        'gold'      => 'GD', 'blue'     => 'BL', 'red'      => 'RD',
        'green'     => 'GR', 'purple'   => 'PU', 'titanium' => 'TI',
        'rose-gold' => 'RG',
    ];

    private function colorAbbr(string $color): string
    {
        return self::COLOR_ABR[$color] ?? strtoupper(substr($color, 0, 2));
    }

    private function storageAbbr(string $storage): string
    {
        return strtoupper(str_replace(['gb', 'tb'], ['G', 'T'], $storage));
    }

    private function createVariants(Product $product, array $vcfg): int
    {
        $mode  = $vcfg['mode'] ?? 'color_storage';
        $total = 0;

        // Attach the attribute(s) used by this product
        $this->attachProductAttributes($product, $mode);

        if ($mode === 'color_storage') {
            $sort = 1;
            foreach ($vcfg['colors'] as $color) {
                foreach ($vcfg['storages'] as $storage => $pricing) {
                    $colorId   = $this->colorIds[$color] ?? null;
                    $storageId = $this->storageIds[$storage] ?? null;
                    if (!$colorId || !$storageId) continue;

                    $sku     = $vcfg['sku_prefix'] . '-' . $this->colorAbbr($color) . '-' . $this->storageAbbr($storage);
                    $stock   = $vcfg['stock_per_variant'] ?? rand(5, 20);
                    $variant = ProductVariant::create([
                        'product_id'          => $product->id,
                        'sku'                 => $sku,
                        'price'               => $pricing[0],
                        'compare_price'       => $pricing[1] ?? null,
                        'cost_price'          => $pricing[2] ?? null,
                        'stock_quantity'      => $stock,
                        'low_stock_threshold' => 3,
                        'is_active'           => true,
                        'sort_order'          => $sort++,
                    ]);

                    DB::table('product_variant_values')->insert([
                        ['variant_id' => $variant->id, 'attribute_value_id' => $colorId],
                        ['variant_id' => $variant->id, 'attribute_value_id' => $storageId],
                    ]);
                    $total += $stock;
                }
            }

        } elseif ($mode === 'color_only') {
            $sort = 1;
            foreach ($vcfg['colors'] as $color => $pricing) {
                $colorId = $this->colorIds[$color] ?? null;
                if (!$colorId) continue;

                $sku     = $vcfg['sku_prefix'] . '-' . $this->colorAbbr($color);
                $stock   = $vcfg['stock_per_variant'] ?? rand(5, 20);
                $variant = ProductVariant::create([
                    'product_id'          => $product->id,
                    'sku'                 => $sku,
                    'price'               => $pricing[0],
                    'compare_price'       => $pricing[1] ?? null,
                    'cost_price'          => $pricing[2] ?? null,
                    'stock_quantity'      => $stock,
                    'low_stock_threshold' => 3,
                    'is_active'           => true,
                    'sort_order'          => $sort++,
                ]);

                DB::table('product_variant_values')->insert([
                    ['variant_id' => $variant->id, 'attribute_value_id' => $colorId],
                ]);
                $total += $stock;
            }

        } elseif ($mode === 'storage_only') {
            $sort = 1;
            foreach ($vcfg['storages'] as $storage => $pricing) {
                $storageId = $this->storageIds[$storage] ?? null;
                if (!$storageId) continue;

                $sku     = $vcfg['sku_prefix'] . '-' . $this->storageAbbr($storage);
                $stock   = $vcfg['stock_per_variant'] ?? rand(5, 20);
                $variant = ProductVariant::create([
                    'product_id'          => $product->id,
                    'sku'                 => $sku,
                    'price'               => $pricing[0],
                    'compare_price'       => $pricing[1] ?? null,
                    'cost_price'          => $pricing[2] ?? null,
                    'stock_quantity'      => $stock,
                    'low_stock_threshold' => 3,
                    'is_active'           => true,
                    'sort_order'          => $sort++,
                ]);

                DB::table('product_variant_values')->insert([
                    ['variant_id' => $variant->id, 'attribute_value_id' => $storageId],
                ]);
                $total += $stock;
            }
        }

        return $total;
    }

    private function attachProductAttributes(Product $product, string $mode): void
    {
        $slugs = match ($mode) {
            'color_storage' => ['color', 'storage'],
            'color_only'    => ['color'],
            'storage_only'  => ['storage'],
            default         => [],
        };

        $ids = Attribute::whereIn('slug', $slugs)->pluck('id')->toArray();
        foreach ($ids as $id) {
            DB::table('product_attributes')->insertOrIgnore([
                'product_id'   => $product->id,
                'attribute_id' => $id,
            ]);
        }
    }

    // ── Media ─────────────────────────────────────────────────────────────────

    private function createMedia(Product $product, string $sku, string $keyword = 'smartphone,technology'): void
    {
        $slug = Str::slug($sku);
        for ($i = 1; $i <= 4; $i++) {
            $lock  = abs(crc32("{$slug}-{$i}")) % 100000;
            $url   = "https://loremflickr.com/800/800/{$keyword}?lock={$lock}";
            $thumb = "https://loremflickr.com/300/300/{$keyword}?lock={$lock}";
            ProductMedia::create([
                'product_id'    => $product->id,
                'type'          => 'image',
                'file_name'     => "{$slug}-{$i}.jpg",
                'file_path'     => "products/seeded/{$slug}-{$i}.jpg",
                'file_url'      => $url,
                'thumbnail_url' => $thumb,
                'mime_type'     => 'image/jpeg',
                'file_size'     => rand(200000, 700000),
                'alt'           => $product->name . ' image ' . $i,
                'title'         => $product->name,
                'sort_order'    => $i,
                'is_primary'    => $i === 1,
            ]);
        }
    }

    // ── SEO ───────────────────────────────────────────────────────────────────

    private function createSeo(Product $product, array $seo): void
    {
        if (empty($seo)) return;
        ProductSeo::create(array_merge(['product_id' => $product->id], $seo));
    }

    // ── Specifications ────────────────────────────────────────────────────────

    private function createSpecs(Product $product, array $specs): void
    {
        foreach ($specs as $i => $spec) {
            ProductSpecification::create([
                'product_id' => $product->id,
                'group'      => $spec['group'],
                'label'      => $spec['label'],
                'value'      => $spec['value'],
                'sort_order' => $i + 1,
            ]);
        }
    }

    // ── Tags ──────────────────────────────────────────────────────────────────

    private function attachTags(Product $product, array $slugs): void
    {
        $ids = array_filter(array_map(fn($s) => $this->tags[$s] ?? null, $slugs));
        if ($ids) {
            $product->tags()->sync($ids);
        }
    }

    // ── Product catalog ───────────────────────────────────────────────────────

    private function catalog(): array
    {
        return [

            // ── 1. iPhone 15 Pro Max ──────────────────────────────────────────
            [
                'sku'               => 'APL-IP15PM',
                'name'              => 'iPhone 15 Pro Max',
                'img'               => 'iphone,apple,smartphone',
                'category'          => 'Smartphones',
                'brand'             => 'Apple',
                'cost_price'        => 870.00,
                'selling_price'     => 1199.00,
                'compare_price'     => 1299.00,
                'weight'            => 0.221,
                'is_featured'       => true,
                'is_best_seller'    => true,
                'short_description' => 'The most powerful iPhone ever with the A17 Pro chip, titanium design, and a 5× Telephoto camera.',
                'description'       => '<p>iPhone 15 Pro Max features the <strong>A17 Pro chip</strong> — the first chip in a smartphone built on 3nm technology. Forged in titanium and featuring a customizable Action button and the most powerful iPhone camera system ever.</p><ul><li>A17 Pro chip with 6-core GPU</li><li>Pro camera system with 5× Telephoto</li><li>Titanium design with Ceramic Shield</li><li>USB 3 speeds with USB-C connector</li></ul>',
                'tags'              => ['apple', 'iphone', '5g', 'pro', 'featured', 'best-seller', 'fast-charging', 'usb-c'],
                'variants'          => [
                    'mode'              => 'color_storage',
                    'sku_prefix'        => 'IP15PM',
                    'stock_per_variant' => 10,
                    'colors'            => ['black', 'white', 'titanium'],
                    'storages'          => [
                        '256gb' => [1199.00, 1299.00, 870.00],
                        '512gb' => [1399.00, 1499.00, 1000.00],
                        '1tb'   => [1599.00, 1699.00, 1150.00],
                    ],
                ],
                'seo' => [
                    'meta_title'       => 'iPhone 15 Pro Max — Buy Online | Mobile Shop',
                    'meta_description' => 'Buy the iPhone 15 Pro Max with A17 Pro chip, titanium design, and 5× telephoto camera. Available in 256GB, 512GB, and 1TB.',
                    'meta_keywords'    => 'iPhone 15 Pro Max, Apple iPhone 15, buy iPhone, iPhone titanium',
                ],
                'specs' => [
                    ['group' => 'Display',       'label' => 'Screen Size',    'value' => '6.7" Super Retina XDR OLED'],
                    ['group' => 'Display',       'label' => 'Resolution',     'value' => '2796 × 1290 at 460 ppi'],
                    ['group' => 'Display',       'label' => 'Refresh Rate',   'value' => 'ProMotion 120 Hz'],
                    ['group' => 'Performance',   'label' => 'Processor',      'value' => 'Apple A17 Pro (3 nm)'],
                    ['group' => 'Performance',   'label' => 'RAM',            'value' => '8 GB'],
                    ['group' => 'Camera',        'label' => 'Main Camera',    'value' => '48 MP, f/1.78, OIS'],
                    ['group' => 'Camera',        'label' => 'Telephoto',      'value' => '12 MP, 5× optical zoom'],
                    ['group' => 'Camera',        'label' => 'Front Camera',   'value' => '12 MP TrueDepth, f/1.9'],
                    ['group' => 'Battery',       'label' => 'Capacity',       'value' => '4422 mAh'],
                    ['group' => 'Battery',       'label' => 'Fast Charging',  'value' => '27 W wired / 15 W MagSafe'],
                    ['group' => 'Connectivity',  'label' => 'Network',        'value' => '5G Sub-6 GHz & mmWave'],
                    ['group' => 'Connectivity',  'label' => 'Port',           'value' => 'USB-C (USB 3)'],
                    ['group' => 'Build',         'label' => 'Body Material',  'value' => 'Grade 5 Titanium'],
                    ['group' => 'Build',         'label' => 'Water Resistance','value' => 'IP68 (6 m for 30 min)'],
                ],
            ],

            // ── 2. iPhone 15 ─────────────────────────────────────────────────
            [
                'sku'               => 'APL-IP15',
                'name'              => 'iPhone 15',
                'img'               => 'iphone,apple,smartphone',
                'category'          => 'Smartphones',
                'brand'             => 'Apple',
                'cost_price'        => 600.00,
                'selling_price'     => 799.00,
                'compare_price'     => 899.00,
                'weight'            => 0.171,
                'is_featured'       => true,
                'short_description' => 'The standard iPhone 15 with Dynamic Island, 48 MP main camera, and USB-C.',
                'description'       => '<p>iPhone 15 brings the <strong>Dynamic Island</strong> to the standard model along with a 48 MP main camera, Ceramic Shield, and USB-C connectivity at a great price.</p>',
                'tags'              => ['apple', 'iphone', '5g', 'featured', 'usb-c'],
                'variants'          => [
                    'mode'              => 'color_storage',
                    'sku_prefix'        => 'IP15',
                    'stock_per_variant' => 15,
                    'colors'            => ['black', 'blue', 'green'],
                    'storages'          => [
                        '128gb' => [799.00, 899.00, 600.00],
                        '256gb' => [899.00, 999.00, 680.00],
                        '512gb' => [1099.00, 1199.00, 820.00],
                    ],
                ],
                'seo' => [
                    'meta_title'       => 'iPhone 15 — Buy Online | Mobile Shop',
                    'meta_description' => 'Buy the iPhone 15 with Dynamic Island, 48 MP camera, and USB-C. Available in 128 GB, 256 GB, and 512 GB.',
                    'meta_keywords'    => 'iPhone 15, Apple iPhone, buy iPhone 15, Dynamic Island',
                ],
                'specs' => [
                    ['group' => 'Display',      'label' => 'Screen Size',   'value' => '6.1" Super Retina XDR OLED'],
                    ['group' => 'Display',      'label' => 'Resolution',    'value' => '2556 × 1179 at 460 ppi'],
                    ['group' => 'Performance',  'label' => 'Processor',     'value' => 'Apple A16 Bionic'],
                    ['group' => 'Performance',  'label' => 'RAM',           'value' => '6 GB'],
                    ['group' => 'Camera',       'label' => 'Main Camera',   'value' => '48 MP, f/1.6'],
                    ['group' => 'Camera',       'label' => 'Front Camera',  'value' => '12 MP TrueDepth'],
                    ['group' => 'Battery',      'label' => 'Capacity',      'value' => '3349 mAh'],
                    ['group' => 'Connectivity', 'label' => 'Network',       'value' => '5G'],
                    ['group' => 'Connectivity', 'label' => 'Port',          'value' => 'USB-C'],
                    ['group' => 'Build',        'label' => 'Water Resistance','value' => 'IP68'],
                ],
            ],

            // ── 3. Samsung Galaxy S24 Ultra ───────────────────────────────────
            [
                'sku'               => 'SAM-S24U',
                'name'              => 'Samsung Galaxy S24 Ultra',
                'img'               => 'samsung,galaxy,smartphone',
                'category'          => 'Smartphones',
                'brand'             => 'Samsung',
                'cost_price'        => 900.00,
                'selling_price'     => 1299.00,
                'compare_price'     => 1449.00,
                'weight'            => 0.232,
                'is_featured'       => true,
                'is_best_seller'    => true,
                'short_description' => 'The ultimate Android flagship with Galaxy AI, built-in S Pen, and a 200 MP camera.',
                'description'       => '<p>Samsung Galaxy S24 Ultra is the pinnacle of mobile innovation. Packed with <strong>Galaxy AI</strong>, a built-in S Pen, and a 200 MP camera, it redefines what a smartphone can do.</p>',
                'tags'              => ['samsung', 'galaxy', '5g', 'ultra', 'pro', 'featured', 'best-seller', 'fast-charging'],
                'variants'          => [
                    'mode'              => 'color_storage',
                    'sku_prefix'        => 'S24U',
                    'stock_per_variant' => 8,
                    'colors'            => ['black', 'titanium'],
                    'storages'          => [
                        '256gb' => [1299.00, 1449.00, 900.00],
                        '512gb' => [1449.00, 1599.00, 1020.00],
                        '1tb'   => [1649.00, 1799.00, 1150.00],
                    ],
                ],
                'seo' => [
                    'meta_title'       => 'Samsung Galaxy S24 Ultra — Buy Online | Mobile Shop',
                    'meta_description' => 'Buy the Samsung Galaxy S24 Ultra with Galaxy AI, built-in S Pen, and 200 MP camera. Available in 256 GB – 1 TB.',
                    'meta_keywords'    => 'Samsung Galaxy S24 Ultra, Galaxy S24, buy Samsung, S Pen flagship',
                ],
                'specs' => [
                    ['group' => 'Display',      'label' => 'Screen Size',   'value' => '6.8" Dynamic AMOLED 2X'],
                    ['group' => 'Display',      'label' => 'Resolution',    'value' => '3088 × 1440 QHD+ at 505 ppi'],
                    ['group' => 'Display',      'label' => 'Refresh Rate',  'value' => 'Adaptive 1–120 Hz'],
                    ['group' => 'Performance',  'label' => 'Processor',     'value' => 'Snapdragon 8 Gen 3 for Galaxy'],
                    ['group' => 'Performance',  'label' => 'RAM',           'value' => '12 GB'],
                    ['group' => 'Camera',       'label' => 'Main Camera',   'value' => '200 MP, f/1.7, OIS'],
                    ['group' => 'Camera',       'label' => 'Telephoto',     'value' => '50 MP, 5× optical zoom'],
                    ['group' => 'Camera',       'label' => 'Front Camera',  'value' => '12 MP, f/2.2'],
                    ['group' => 'Battery',      'label' => 'Capacity',      'value' => '5000 mAh'],
                    ['group' => 'Battery',      'label' => 'Fast Charging', 'value' => '45 W wired / 15 W wireless'],
                    ['group' => 'Connectivity', 'label' => 'Network',       'value' => '5G'],
                    ['group' => 'Connectivity', 'label' => 'Port',          'value' => 'USB-C (USB 3.2)'],
                    ['group' => 'Build',        'label' => 'Stylus',        'value' => 'Built-in S Pen'],
                    ['group' => 'Build',        'label' => 'Water Resistance','value' => 'IP68'],
                ],
            ],

            // ── 4. Samsung Galaxy A55 5G ──────────────────────────────────────
            [
                'sku'               => 'SAM-A55',
                'name'              => 'Samsung Galaxy A55 5G',
                'img'               => 'samsung,android,smartphone',
                'category'          => 'Smartphones',
                'brand'             => 'Samsung',
                'cost_price'        => 280.00,
                'selling_price'     => 449.00,
                'compare_price'     => 499.00,
                'weight'            => 0.213,
                'is_new_arrival'    => true,
                'short_description' => 'Premium mid-range 5G smartphone with 50 MP OIS camera and IP67 water resistance.',
                'description'       => '<p>The Galaxy A55 5G delivers premium features at a mid-range price — including a bright 120 Hz AMOLED display, 50 MP OIS camera, and military-grade durability.</p>',
                'tags'              => ['samsung', 'galaxy', '5g', 'new-arrival', 'fast-charging'],
                'variants'          => [
                    'mode'              => 'color_storage',
                    'sku_prefix'        => 'A55',
                    'stock_per_variant' => 20,
                    'colors'            => ['black', 'blue', 'purple'],
                    'storages'          => [
                        '128gb' => [449.00, 499.00, 280.00],
                        '256gb' => [499.00, 549.00, 320.00],
                    ],
                ],
                'seo' => [
                    'meta_title'       => 'Samsung Galaxy A55 5G — Buy Online | Mobile Shop',
                    'meta_description' => 'Buy the Samsung Galaxy A55 5G with 120 Hz AMOLED, 50 MP OIS camera, and IP67 rating. Best mid-range 5G phone.',
                    'meta_keywords'    => 'Samsung Galaxy A55, Galaxy A55 5G, mid-range 5G, buy Samsung',
                ],
                'specs' => [
                    ['group' => 'Display',      'label' => 'Screen Size',   'value' => '6.6" Super AMOLED 120 Hz'],
                    ['group' => 'Performance',  'label' => 'Processor',     'value' => 'Exynos 1480 (4 nm)'],
                    ['group' => 'Performance',  'label' => 'RAM',           'value' => '8 GB'],
                    ['group' => 'Camera',       'label' => 'Main Camera',   'value' => '50 MP, f/1.8, OIS'],
                    ['group' => 'Battery',      'label' => 'Capacity',      'value' => '5000 mAh'],
                    ['group' => 'Battery',      'label' => 'Fast Charging', 'value' => '25 W'],
                    ['group' => 'Connectivity', 'label' => 'Network',       'value' => '5G'],
                    ['group' => 'Build',        'label' => 'Water Resistance','value' => 'IP67'],
                ],
            ],

            // ── 5. Xiaomi 14 ──────────────────────────────────────────────────
            [
                'sku'               => 'XMI-14',
                'name'              => 'Xiaomi 14',
                'img'               => 'xiaomi,smartphone,android',
                'category'          => 'Smartphones',
                'brand'             => 'Xiaomi',
                'cost_price'        => 550.00,
                'selling_price'     => 799.00,
                'compare_price'     => 899.00,
                'weight'            => 0.193,
                'is_featured'       => true,
                'short_description' => 'Leica co-engineered cameras with Snapdragon 8 Gen 3 and 90 W HyperCharge.',
                'description'       => '<p>The Xiaomi 14 combines a <strong>Leica-engineered triple camera</strong> system with Qualcomm\'s Snapdragon 8 Gen 3 chip and blazing 90 W wired charging.</p>',
                'tags'              => ['xiaomi', '5g', 'pro', 'featured', 'fast-charging', 'usb-c'],
                'variants'          => [
                    'mode'              => 'color_storage',
                    'sku_prefix'        => 'XMI14',
                    'stock_per_variant' => 12,
                    'colors'            => ['black', 'white'],
                    'storages'          => [
                        '256gb' => [799.00, 899.00, 550.00],
                        '512gb' => [949.00, 1049.00, 670.00],
                    ],
                ],
                'seo' => [
                    'meta_title'       => 'Xiaomi 14 — Buy Online | Mobile Shop',
                    'meta_description' => 'Buy the Xiaomi 14 with Snapdragon 8 Gen 3, Leica cameras, and 90 W HyperCharge. Best flagship value.',
                    'meta_keywords'    => 'Xiaomi 14, Xiaomi flagship, buy Xiaomi, Leica camera phone',
                ],
                'specs' => [
                    ['group' => 'Display',      'label' => 'Screen Size',   'value' => '6.36" LTPO AMOLED 120 Hz'],
                    ['group' => 'Performance',  'label' => 'Processor',     'value' => 'Snapdragon 8 Gen 3'],
                    ['group' => 'Performance',  'label' => 'RAM',           'value' => '12 GB'],
                    ['group' => 'Camera',       'label' => 'Main Camera',   'value' => '50 MP Leica Summilux, f/1.6'],
                    ['group' => 'Camera',       'label' => 'Telephoto',     'value' => '50 MP, 3.2× optical zoom'],
                    ['group' => 'Battery',      'label' => 'Capacity',      'value' => '4610 mAh'],
                    ['group' => 'Battery',      'label' => 'Fast Charging', 'value' => '90 W wired / 50 W wireless'],
                    ['group' => 'Connectivity', 'label' => 'Network',       'value' => '5G'],
                ],
            ],

            // ── 6. OnePlus 12 ─────────────────────────────────────────────────
            [
                'sku'               => 'OPL-12',
                'name'              => 'OnePlus 12',
                'img'               => 'oneplus,smartphone,android',
                'category'          => 'Smartphones',
                'brand'             => 'OnePlus',
                'cost_price'        => 600.00,
                'selling_price'     => 899.00,
                'compare_price'     => 999.00,
                'weight'            => 0.220,
                'is_featured'       => true,
                'short_description' => 'Hasselblad-tuned camera, Snapdragon 8 Gen 3, and 100 W SUPERVOOC charging.',
                'description'       => '<p>OnePlus 12 delivers flagship performance with <strong>Hasselblad-tuned cameras</strong>, Snapdragon 8 Gen 3, and industry-leading 100 W SUPERVOOC charging that fills from 0–100% in just 26 minutes.</p>',
                'tags'              => ['oneplus', '5g', 'pro', 'featured', 'fast-charging'],
                'variants'          => [
                    'mode'              => 'color_storage',
                    'sku_prefix'        => 'OP12',
                    'stock_per_variant' => 10,
                    'colors'            => ['black', 'green'],
                    'storages'          => [
                        '256gb' => [899.00, 999.00, 600.00],
                        '512gb' => [999.00, 1099.00, 700.00],
                    ],
                ],
                'seo' => [
                    'meta_title'       => 'OnePlus 12 — Buy Online | Mobile Shop',
                    'meta_description' => 'Buy OnePlus 12 with Snapdragon 8 Gen 3, Hasselblad cameras, and 100 W SUPERVOOC charging.',
                    'meta_keywords'    => 'OnePlus 12, buy OnePlus, Hasselblad camera phone, 100W charging',
                ],
                'specs' => [
                    ['group' => 'Display',      'label' => 'Screen Size',   'value' => '6.82" LTPO 3.0 AMOLED 120 Hz'],
                    ['group' => 'Performance',  'label' => 'Processor',     'value' => 'Snapdragon 8 Gen 3'],
                    ['group' => 'Performance',  'label' => 'RAM',           'value' => '12 GB'],
                    ['group' => 'Camera',       'label' => 'Main Camera',   'value' => '50 MP Hasselblad, f/1.6'],
                    ['group' => 'Camera',       'label' => 'Telephoto',     'value' => '64 MP, 3× optical zoom'],
                    ['group' => 'Battery',      'label' => 'Capacity',      'value' => '5400 mAh'],
                    ['group' => 'Battery',      'label' => 'Fast Charging', 'value' => '100 W SUPERVOOC'],
                    ['group' => 'Connectivity', 'label' => 'Network',       'value' => '5G'],
                ],
            ],

            // ── 7. Google Pixel 8 Pro ─────────────────────────────────────────
            [
                'sku'               => 'GGL-PX8P',
                'name'              => 'Google Pixel 8 Pro',
                'img'               => 'google,pixel,smartphone',
                'category'          => 'Smartphones',
                'brand'             => 'Google',
                'cost_price'        => 680.00,
                'selling_price'     => 999.00,
                'compare_price'     => 1099.00,
                'weight'            => 0.213,
                'is_featured'       => true,
                'short_description' => 'Google\'s most advanced AI-powered phone with Tensor G3 chip and 50 MP pro camera.',
                'description'       => '<p>Pixel 8 Pro runs Google\'s <strong>Tensor G3 chip</strong> for unmatched on-device AI capabilities including Best Take, Magic Eraser, and Audio Magic Eraser, plus a professional 50 MP triple camera array.</p>',
                'tags'              => ['5g', 'pro', 'featured', 'fast-charging', 'usb-c'],
                'variants'          => [
                    'mode'              => 'color_storage',
                    'sku_prefix'        => 'PX8P',
                    'stock_per_variant' => 8,
                    'colors'            => ['black', 'white'],
                    'storages'          => [
                        '128gb' => [999.00, 1099.00, 680.00],
                        '256gb' => [1059.00, 1159.00, 740.00],
                        '512gb' => [1179.00, 1279.00, 840.00],
                    ],
                ],
                'seo' => [
                    'meta_title'       => 'Google Pixel 8 Pro — Buy Online | Mobile Shop',
                    'meta_description' => 'Buy Google Pixel 8 Pro with Tensor G3, AI-powered camera, and 7 years of OS updates.',
                    'meta_keywords'    => 'Google Pixel 8 Pro, Pixel 8, buy Google Pixel, AI smartphone',
                ],
                'specs' => [
                    ['group' => 'Display',      'label' => 'Screen Size',   'value' => '6.7" LTPO OLED 120 Hz'],
                    ['group' => 'Performance',  'label' => 'Processor',     'value' => 'Google Tensor G3'],
                    ['group' => 'Performance',  'label' => 'RAM',           'value' => '12 GB'],
                    ['group' => 'Camera',       'label' => 'Main Camera',   'value' => '50 MP, f/1.68, OIS'],
                    ['group' => 'Camera',       'label' => 'Telephoto',     'value' => '48 MP, 5× optical zoom'],
                    ['group' => 'Battery',      'label' => 'Capacity',      'value' => '5050 mAh'],
                    ['group' => 'Battery',      'label' => 'Fast Charging', 'value' => '30 W wired / 23 W wireless'],
                    ['group' => 'Connectivity', 'label' => 'Network',       'value' => '5G'],
                    ['group' => 'Software',     'label' => 'OS Updates',    'value' => '7 years guaranteed'],
                ],
            ],

            // ── 8. Realme GT 6 ───────────────────────────────────────────────
            [
                'sku'               => 'RME-GT6',
                'name'              => 'Realme GT 6',
                'img'               => 'realme,smartphone,android',
                'category'          => 'Smartphones',
                'brand'             => 'Realme',
                'cost_price'        => 320.00,
                'selling_price'     => 499.00,
                'compare_price'     => 579.00,
                'weight'            => 0.199,
                'is_new_arrival'    => true,
                'short_description' => 'Snapdragon 8s Gen 3, 6000 mAh battery, and 120 W SUPERVOOC at a disruptive price.',
                'description'       => '<p>Realme GT 6 packs a <strong>Snapdragon 8s Gen 3</strong> chipset, a massive 6000 mAh battery, and 120 W ultra-fast charging — making it the best performance-per-pound smartphone in its class.</p>',
                'tags'              => ['5g', 'new-arrival', 'fast-charging'],
                'variants'          => [
                    'mode'              => 'color_storage',
                    'sku_prefix'        => 'GT6',
                    'stock_per_variant' => 18,
                    'colors'            => ['black', 'blue'],
                    'storages'          => [
                        '128gb' => [499.00, 579.00, 320.00],
                        '256gb' => [549.00, 629.00, 360.00],
                    ],
                ],
                'seo' => [
                    'meta_title'       => 'Realme GT 6 — Buy Online | Mobile Shop',
                    'meta_description' => 'Buy Realme GT 6 with Snapdragon 8s Gen 3, 6000 mAh battery, and 120 W fast charging.',
                    'meta_keywords'    => 'Realme GT 6, buy Realme, fast charging phone, Snapdragon 8s',
                ],
                'specs' => [
                    ['group' => 'Display',      'label' => 'Screen Size',   'value' => '6.78" AMOLED 120 Hz'],
                    ['group' => 'Performance',  'label' => 'Processor',     'value' => 'Snapdragon 8s Gen 3'],
                    ['group' => 'Performance',  'label' => 'RAM',           'value' => '8 GB'],
                    ['group' => 'Camera',       'label' => 'Main Camera',   'value' => '50 MP Sony IMX906, OIS'],
                    ['group' => 'Battery',      'label' => 'Capacity',      'value' => '6000 mAh'],
                    ['group' => 'Battery',      'label' => 'Fast Charging', 'value' => '120 W SUPERVOOC'],
                    ['group' => 'Connectivity', 'label' => 'Network',       'value' => '5G'],
                ],
            ],

            // ── 9. Apple iPad Pro 13" M4 ──────────────────────────────────────
            [
                'sku'               => 'APL-IPADPRO13',
                'name'              => 'Apple iPad Pro 13" M4',
                'img'               => 'ipad,apple,tablet',
                'category'          => 'Tablets',
                'brand'             => 'Apple',
                'cost_price'        => 800.00,
                'selling_price'     => 1099.00,
                'compare_price'     => 1199.00,
                'weight'            => 0.579,
                'is_featured'       => true,
                'short_description' => 'The thinnest Apple product ever with M4 chip and Ultra Retina XDR OLED display.',
                'description'       => '<p>iPad Pro 13" M4 features the <strong>Ultra Retina XDR OLED tandem display</strong> — the most advanced display Apple has ever shipped — combined with the blazing-fast M4 chip for pro-level performance in an impossibly thin form factor.</p>',
                'tags'              => ['apple', 'ipad', 'featured', 'usb-c', 'pro'],
                'variants'          => [
                    'mode'              => 'storage_only',
                    'sku_prefix'        => 'IPDP13',
                    'stock_per_variant' => 12,
                    'storages'          => [
                        '256gb' => [1099.00, 1199.00, 800.00],
                        '512gb' => [1299.00, 1399.00, 960.00],
                        '1tb'   => [1699.00, 1799.00, 1250.00],
                    ],
                ],
                'seo' => [
                    'meta_title'       => 'Apple iPad Pro 13" M4 — Buy Online | Mobile Shop',
                    'meta_description' => 'Buy the iPad Pro 13" M4 with Ultra Retina XDR OLED display, M4 chip, and Apple Pencil Pro support.',
                    'meta_keywords'    => 'iPad Pro 13 M4, Apple iPad Pro, buy iPad Pro, M4 tablet',
                ],
                'specs' => [
                    ['group' => 'Display',      'label' => 'Screen Size',   'value' => '13" Ultra Retina XDR OLED'],
                    ['group' => 'Display',      'label' => 'Resolution',    'value' => '2752 × 2064 at 264 ppi'],
                    ['group' => 'Performance',  'label' => 'Processor',     'value' => 'Apple M4'],
                    ['group' => 'Performance',  'label' => 'RAM',           'value' => '8 GB (16 GB on 1 TB)'],
                    ['group' => 'Camera',       'label' => 'Rear Camera',   'value' => '12 MP Wide, f/1.8'],
                    ['group' => 'Battery',      'label' => 'Battery Life',  'value' => 'Up to 10 hours'],
                    ['group' => 'Connectivity', 'label' => 'Port',          'value' => 'Thunderbolt 4 / USB 4'],
                    ['group' => 'Build',        'label' => 'Thickness',     'value' => '5.1 mm (world\'s thinnest iPad)'],
                ],
            ],

            // ── 10. Samsung Galaxy Tab S9+ ────────────────────────────────────
            [
                'sku'               => 'SAM-TABS9P',
                'name'              => 'Samsung Galaxy Tab S9+',
                'img'               => 'samsung,tablet,android',
                'category'          => 'Tablets',
                'brand'             => 'Samsung',
                'cost_price'        => 650.00,
                'selling_price'     => 999.00,
                'compare_price'     => 1099.00,
                'weight'            => 0.590,
                'short_description' => 'Dynamic AMOLED 2X display, IP68, and S Pen included — Samsung\'s best Android tablet.',
                'description'       => '<p>Galaxy Tab S9+ features a stunning <strong>12.4" Dynamic AMOLED 2X</strong> display with 120 Hz, IP68 water resistance, and the S Pen included in the box — all powered by Snapdragon 8 Gen 2.</p>',
                'tags'              => ['samsung', 'galaxy', 'featured', 'usb-c', 'fast-charging'],
                'variants'          => [
                    'mode'              => 'color_storage',
                    'sku_prefix'        => 'TABS9P',
                    'stock_per_variant' => 8,
                    'colors'            => ['black', 'silver'],
                    'storages'          => [
                        '128gb' => [999.00, 1099.00, 650.00],
                        '256gb' => [1099.00, 1199.00, 730.00],
                    ],
                ],
                'seo' => [
                    'meta_title'       => 'Samsung Galaxy Tab S9+ — Buy Online | Mobile Shop',
                    'meta_description' => 'Buy Samsung Galaxy Tab S9+ with 12.4" AMOLED 120 Hz, S Pen, and IP68. Best Android tablet.',
                    'meta_keywords'    => 'Samsung Galaxy Tab S9+, Galaxy Tab, buy Samsung tablet, Android tablet',
                ],
                'specs' => [
                    ['group' => 'Display',      'label' => 'Screen Size',   'value' => '12.4" Dynamic AMOLED 2X 120 Hz'],
                    ['group' => 'Performance',  'label' => 'Processor',     'value' => 'Snapdragon 8 Gen 2'],
                    ['group' => 'Performance',  'label' => 'RAM',           'value' => '12 GB'],
                    ['group' => 'Camera',       'label' => 'Rear Camera',   'value' => '13 MP + 8 MP Ultra-Wide'],
                    ['group' => 'Battery',      'label' => 'Capacity',      'value' => '10090 mAh'],
                    ['group' => 'Battery',      'label' => 'Fast Charging', 'value' => '45 W'],
                    ['group' => 'Build',        'label' => 'Water Resistance','value' => 'IP68'],
                    ['group' => 'Build',        'label' => 'Stylus',        'value' => 'S Pen included'],
                ],
            ],

            // ── 11. Xiaomi Pad 6 ─────────────────────────────────────────────
            [
                'sku'               => 'XMI-PAD6',
                'name'              => 'Xiaomi Pad 6',
                'img'               => 'xiaomi,tablet,android',
                'category'          => 'Tablets',
                'brand'             => 'Xiaomi',
                'cost_price'        => 220.00,
                'selling_price'     => 349.00,
                'compare_price'     => 399.00,
                'weight'            => 0.490,
                'is_new_arrival'    => true,
                'short_description' => '144 Hz 2.8K display with Snapdragon 870 and 33 W fast charging.',
                'description'       => '<p>Xiaomi Pad 6 delivers a <strong>2.8K 144 Hz IPS display</strong> with Snapdragon 870 chipset and 33 W wired charging — making it the best budget flagship tablet available.</p>',
                'tags'              => ['xiaomi', 'new-arrival', 'fast-charging', 'usb-c'],
                'variants'          => [
                    'mode'              => 'storage_only',
                    'sku_prefix'        => 'XPAD6',
                    'stock_per_variant' => 25,
                    'storages'          => [
                        '128gb' => [349.00, 399.00, 220.00],
                        '256gb' => [399.00, 449.00, 255.00],
                    ],
                ],
                'seo' => [
                    'meta_title'       => 'Xiaomi Pad 6 — Buy Online | Mobile Shop',
                    'meta_description' => 'Buy Xiaomi Pad 6 with 2.8K 144 Hz display, Snapdragon 870, and 33 W fast charging at the best price.',
                    'meta_keywords'    => 'Xiaomi Pad 6, buy Xiaomi tablet, budget flagship tablet',
                ],
                'specs' => [
                    ['group' => 'Display',      'label' => 'Screen Size',   'value' => '11" IPS 2.8K 144 Hz'],
                    ['group' => 'Performance',  'label' => 'Processor',     'value' => 'Snapdragon 870'],
                    ['group' => 'Performance',  'label' => 'RAM',           'value' => '8 GB'],
                    ['group' => 'Camera',       'label' => 'Rear Camera',   'value' => '13 MP'],
                    ['group' => 'Battery',      'label' => 'Capacity',      'value' => '8840 mAh'],
                    ['group' => 'Battery',      'label' => 'Fast Charging', 'value' => '33 W'],
                ],
            ],

            // ── 12. Apple AirPods Pro 2nd Gen ─────────────────────────────────
            [
                'sku'               => 'APL-APP2',
                'name'              => 'Apple AirPods Pro 2nd Generation',
                'img'               => 'airpods,apple,earbuds',
                'category'          => 'Earphones',
                'brand'             => 'Apple',
                'cost_price'        => 160.00,
                'selling_price'     => 249.00,
                'compare_price'     => 279.00,
                'stock'             => 60,
                'weight'            => 0.056,
                'is_best_seller'    => true,
                'is_featured'       => true,
                'short_description' => 'Up to 2× more active noise cancellation with Adaptive Audio and MagSafe charging.',
                'description'       => '<p>AirPods Pro 2nd generation feature the <strong>H2 chip</strong> for up to 2× more active noise cancellation, Transparency mode, Adaptive Audio, and Personalized Spatial Audio. MagSafe charging case included.</p>',
                'tags'              => ['apple', 'airpods', 'wireless', 'best-seller', 'featured', 'magsafe'],
                'seo' => [
                    'meta_title'       => 'Apple AirPods Pro 2nd Gen — Buy Online | Mobile Shop',
                    'meta_description' => 'Buy AirPods Pro 2nd generation with 2× ANC, Adaptive Audio, and MagSafe charging case.',
                    'meta_keywords'    => 'AirPods Pro 2, Apple AirPods, buy AirPods, noise cancelling earbuds',
                ],
                'specs' => [
                    ['group' => 'Audio',       'label' => 'Chip',            'value' => 'Apple H2'],
                    ['group' => 'Audio',       'label' => 'ANC',             'value' => 'Up to 2× improved Active Noise Cancellation'],
                    ['group' => 'Audio',       'label' => 'Spatial Audio',   'value' => 'Yes, Personalized'],
                    ['group' => 'Battery',     'label' => 'Earbuds Battery', 'value' => 'Up to 6 hours (ANC on)'],
                    ['group' => 'Battery',     'label' => 'Total Battery',   'value' => 'Up to 30 hours with case'],
                    ['group' => 'Battery',     'label' => 'Charging',        'value' => 'MagSafe / Lightning / USB-C'],
                    ['group' => 'Connectivity','label' => 'Bluetooth',       'value' => 'Bluetooth 5.3'],
                    ['group' => 'Build',       'label' => 'Water Resistance','value' => 'IPX4 (buds & case)'],
                ],
            ],

            // ── 13. Samsung Galaxy Buds 2 Pro ─────────────────────────────────
            [
                'sku'               => 'SAM-GBD2P',
                'name'              => 'Samsung Galaxy Buds 2 Pro',
                'img'               => 'samsung,earbuds,wireless',
                'category'          => 'Earphones',
                'brand'             => 'Samsung',
                'cost_price'        => 140.00,
                'selling_price'     => 229.00,
                'compare_price'     => 259.00,
                'weight'            => 0.061,
                'short_description' => 'Hi-Fi 24-bit audio, 3-mic ANC, and 360° Audio on the most compact Galaxy Buds ever.',
                'description'       => '<p>Galaxy Buds 2 Pro deliver <strong>Hi-Fi 24-bit audio</strong>, the most effective 3-mic ANC Samsung has made, and 360° Audio — all in the smallest, lightest Galaxy Buds form factor.</p>',
                'tags'              => ['samsung', 'wireless', 'fast-charging'],
                'variants'          => [
                    'mode'              => 'color_only',
                    'sku_prefix'        => 'GBD2P',
                    'stock_per_variant' => 20,
                    'colors'            => [
                        'black'  => [229.00, 259.00, 140.00],
                        'white'  => [229.00, 259.00, 140.00],
                        'purple' => [229.00, 259.00, 140.00],
                    ],
                ],
                'seo' => [
                    'meta_title'       => 'Samsung Galaxy Buds 2 Pro — Buy Online | Mobile Shop',
                    'meta_description' => 'Buy Samsung Galaxy Buds 2 Pro with Hi-Fi 24-bit audio and 3-mic ANC.',
                    'meta_keywords'    => 'Galaxy Buds 2 Pro, Samsung earbuds, buy Galaxy Buds, ANC earphones',
                ],
                'specs' => [
                    ['group' => 'Audio',       'label' => 'Audio Quality',   'value' => 'Hi-Fi 24-bit / 96 kHz'],
                    ['group' => 'Audio',       'label' => 'ANC',             'value' => 'Intelligent Active Noise Cancellation'],
                    ['group' => 'Battery',     'label' => 'Earbuds Battery', 'value' => '5 hours (ANC on)'],
                    ['group' => 'Battery',     'label' => 'Total Battery',   'value' => '18 hours with case'],
                    ['group' => 'Connectivity','label' => 'Bluetooth',       'value' => 'Bluetooth 5.3'],
                    ['group' => 'Build',       'label' => 'Water Resistance','value' => 'IPX7'],
                ],
            ],

            // ── 14. Sony WH-1000XM5 ───────────────────────────────────────────
            [
                'sku'               => 'SNY-WH1000XM5',
                'name'              => 'Sony WH-1000XM5 Headphones',
                'img'               => 'sony,headphones,wireless',
                'category'          => 'Earphones',
                'brand'             => 'Sony',
                'cost_price'        => 230.00,
                'selling_price'     => 349.00,
                'compare_price'     => 399.00,
                'weight'            => 0.250,
                'is_best_seller'    => true,
                'short_description' => 'Industry-leading noise cancellation with 8 mics, 30 hr battery, and Multipoint connection.',
                'description'       => '<p>Sony WH-1000XM5 features the best noise cancellation ever with <strong>8 microphones and two processors</strong>, 30-hour battery life, and Multipoint connection for simultaneous pairing to two devices.</p>',
                'tags'              => ['best-seller', 'wireless', 'fast-charging'],
                'variants'          => [
                    'mode'              => 'color_only',
                    'sku_prefix'        => 'WH1XM5',
                    'stock_per_variant' => 15,
                    'colors'            => [
                        'black' => [349.00, 399.00, 230.00],
                        'silver' => [349.00, 399.00, 230.00],
                    ],
                ],
                'seo' => [
                    'meta_title'       => 'Sony WH-1000XM5 — Buy Online | Mobile Shop',
                    'meta_description' => 'Buy Sony WH-1000XM5 with industry-leading noise cancellation, 30-hour battery, and 8-mic array.',
                    'meta_keywords'    => 'Sony WH-1000XM5, Sony headphones, best ANC headphones, buy Sony',
                ],
                'specs' => [
                    ['group' => 'Audio',       'label' => 'Driver',         'value' => '30 mm, Dynamic'],
                    ['group' => 'Audio',       'label' => 'ANC Mics',       'value' => '8 microphones, 2 processors'],
                    ['group' => 'Audio',       'label' => 'Codec',          'value' => 'LDAC, AAC, SBC'],
                    ['group' => 'Battery',     'label' => 'Battery Life',   'value' => '30 hours (ANC on)'],
                    ['group' => 'Battery',     'label' => 'Quick Charge',   'value' => '3 min charge = 3 hours playback'],
                    ['group' => 'Connectivity','label' => 'Bluetooth',      'value' => 'Bluetooth 5.2, Multipoint'],
                    ['group' => 'Build',       'label' => 'Foldable',       'value' => 'Yes'],
                ],
            ],

            // ── 15. Apple Watch Series 9 ──────────────────────────────────────
            [
                'sku'               => 'APL-WS9',
                'name'              => 'Apple Watch Series 9 GPS',
                'img'               => 'apple,watch,smartwatch',
                'category'          => 'Accessories',
                'brand'             => 'Apple',
                'cost_price'        => 260.00,
                'selling_price'     => 399.00,
                'compare_price'     => 449.00,
                'weight'            => 0.039,
                'is_featured'       => true,
                'is_best_seller'    => true,
                'short_description' => 'S9 chip with Double Tap gesture, always-on Retina display, and all-day 18 hr battery.',
                'description'       => '<p>Apple Watch Series 9 brings the <strong>new S9 chip</strong> with a revolutionary Double Tap gesture, the brightest Apple Watch display ever at 2000 nits, and powerful health features.</p>',
                'tags'              => ['apple', 'featured', 'best-seller', 'magsafe', 'wireless'],
                'variants'          => [
                    'mode'              => 'color_only',
                    'sku_prefix'        => 'AW9',
                    'stock_per_variant' => 15,
                    'colors'            => [
                        'black'  => [399.00, 449.00, 260.00],
                        'white'  => [399.00, 449.00, 260.00],
                        'red'    => [399.00, 449.00, 260.00],
                        'blue'   => [399.00, 449.00, 260.00],
                    ],
                ],
                'seo' => [
                    'meta_title'       => 'Apple Watch Series 9 GPS — Buy Online | Mobile Shop',
                    'meta_description' => 'Buy Apple Watch Series 9 with Double Tap, S9 chip, 2000-nit display, and 18-hour battery.',
                    'meta_keywords'    => 'Apple Watch Series 9, buy Apple Watch, smartwatch, Apple Watch GPS',
                ],
                'specs' => [
                    ['group' => 'Display',     'label' => 'Display',       'value' => 'Always-On Retina LTPO OLED, 2000 nits'],
                    ['group' => 'Performance', 'label' => 'Chip',          'value' => 'Apple S9 SiP'],
                    ['group' => 'Health',      'label' => 'Sensors',       'value' => 'ECG, Blood Oxygen, Crash Detection, Temperature'],
                    ['group' => 'Battery',     'label' => 'Battery Life',  'value' => 'Up to 18 hours'],
                    ['group' => 'Battery',     'label' => 'Charging',      'value' => 'Magnetic Fast Charging USB-C'],
                    ['group' => 'Build',       'label' => 'Water Resistance','value' => '50 m (WR50)'],
                    ['group' => 'Build',       'label' => 'Material',      'value' => 'Aluminium case'],
                ],
            ],

            // ── 16. Samsung Galaxy Watch 6 ────────────────────────────────────
            [
                'sku'               => 'SAM-GW6',
                'name'              => 'Samsung Galaxy Watch 6',
                'img'               => 'samsung,watch,smartwatch',
                'category'          => 'Accessories',
                'brand'             => 'Samsung',
                'cost_price'        => 190.00,
                'selling_price'     => 299.00,
                'compare_price'     => 349.00,
                'weight'            => 0.059,
                'is_new_arrival'    => true,
                'short_description' => 'Advanced sleep coaching, sapphire crystal glass, and 3-day battery life.',
                'description'       => '<p>Samsung Galaxy Watch 6 features a larger 2000-nit AMOLED display with sapphire crystal glass, advanced sleep coaching with Sleep Score, and Exynos W930 chip for up to 3-day battery life.</p>',
                'tags'              => ['samsung', 'new-arrival', 'wireless', 'fast-charging'],
                'variants'          => [
                    'mode'              => 'color_only',
                    'sku_prefix'        => 'GW6',
                    'stock_per_variant' => 18,
                    'colors'            => [
                        'black'  => [299.00, 349.00, 190.00],
                        'gold'   => [299.00, 349.00, 190.00],
                        'silver' => [299.00, 349.00, 190.00],
                    ],
                ],
                'seo' => [
                    'meta_title'       => 'Samsung Galaxy Watch 6 — Buy Online | Mobile Shop',
                    'meta_description' => 'Buy Samsung Galaxy Watch 6 with sapphire glass, advanced sleep tracking, and 3-day battery.',
                    'meta_keywords'    => 'Samsung Galaxy Watch 6, buy Samsung smartwatch, Galaxy Watch',
                ],
                'specs' => [
                    ['group' => 'Display',     'label' => 'Display',        'value' => '1.3" Super AMOLED 2000 nits'],
                    ['group' => 'Performance', 'label' => 'Processor',      'value' => 'Exynos W930'],
                    ['group' => 'Health',      'label' => 'Sensors',        'value' => 'BioActive Sensor, ECG, Temperature'],
                    ['group' => 'Battery',     'label' => 'Battery Life',   'value' => 'Up to 40 hours'],
                    ['group' => 'Battery',     'label' => 'Fast Charging',  'value' => 'Wireless fast charging'],
                    ['group' => 'Build',       'label' => 'Glass',          'value' => 'Sapphire Crystal Glass'],
                    ['group' => 'Build',       'label' => 'Water Resistance','value' => '5 ATM + IP68'],
                ],
            ],

            // ── 17. Anker 65W GaN Charger ─────────────────────────────────────
            [
                'sku'               => 'ANK-65W',
                'name'              => 'Anker 65W GaN Charger (3-Port)',
                'img'               => 'charger,usb,technology',
                'category'          => 'Chargers',
                'brand'             => 'Anker',
                'cost_price'        => 18.00,
                'selling_price'     => 35.99,
                'compare_price'     => 44.99,
                'stock'             => 100,
                'weight'            => 0.135,
                'is_best_seller'    => true,
                'is_new_arrival'    => false,
                'short_description' => 'Charge 3 devices simultaneously with 65 W GaN technology in a pocketable size.',
                'description'       => '<p>Anker 65W GaN Charger uses next-gen <strong>GaN II technology</strong> to deliver 65 W total power through 2 USB-C and 1 USB-A ports — all from a charger barely bigger than an Apple 30 W adapter.</p>',
                'tags'              => ['fast-charging', 'usb-c', 'best-seller', 'accessories'],
                'seo' => [
                    'meta_title'       => 'Anker 65W GaN Charger — Buy Online | Mobile Shop',
                    'meta_description' => 'Buy Anker 65W 3-port GaN charger. Charge laptop, phone, and tablet simultaneously.',
                    'meta_keywords'    => 'Anker 65W charger, GaN charger, fast charger, USB-C charger',
                ],
                'specs' => [
                    ['group' => 'Power',        'label' => 'Total Output',  'value' => '65 W'],
                    ['group' => 'Power',        'label' => 'USB-C 1',       'value' => 'Up to 65 W (PD 3.0)'],
                    ['group' => 'Power',        'label' => 'USB-C 2',       'value' => 'Up to 18 W'],
                    ['group' => 'Power',        'label' => 'USB-A',         'value' => 'Up to 12 W'],
                    ['group' => 'Technology',   'label' => 'Technology',    'value' => 'GaN II'],
                    ['group' => 'Compatibility','label' => 'Compatible with','value' => 'MacBook Air, iPad Pro, iPhone, Galaxy'],
                    ['group' => 'Build',        'label' => 'Dimensions',    'value' => '36.2 × 36.2 × 33 mm'],
                ],
            ],

            // ── 18. Apple 20W USB-C Power Adapter ────────────────────────────
            [
                'sku'               => 'APL-20W',
                'name'              => 'Apple 20W USB-C Power Adapter',
                'img'               => 'apple,charger,usb',
                'category'          => 'Chargers',
                'brand'             => 'Apple',
                'cost_price'        => 10.00,
                'selling_price'     => 19.99,
                'compare_price'     => 24.99,
                'stock'             => 150,
                'weight'            => 0.045,
                'is_best_seller'    => true,
                'short_description' => 'Apple\'s compact 20 W USB-C adapter for fast charging iPhone 8 and later.',
                'description'       => '<p>The official <strong>Apple 20W USB-C Power Adapter</strong> delivers fast charging for iPhone 8 and later, charges iPad Pro up to 18 W, and is compatible with any USB-C device.</p>',
                'tags'              => ['apple', 'fast-charging', 'usb-c', 'best-seller', 'accessories'],
                'seo' => [
                    'meta_title'       => 'Apple 20W USB-C Power Adapter — Buy Online | Mobile Shop',
                    'meta_description' => 'Buy the genuine Apple 20W USB-C Power Adapter for fast charging iPhone and iPad.',
                    'meta_keywords'    => 'Apple 20W charger, USB-C charger, iPhone charger, Apple adapter',
                ],
                'specs' => [
                    ['group' => 'Power',        'label' => 'Output',         'value' => '20 W (USB-C PD)'],
                    ['group' => 'Compatibility','label' => 'Compatible with', 'value' => 'iPhone 8+, iPad Pro/Air/mini, AirPods'],
                    ['group' => 'Build',        'label' => 'Connector',      'value' => 'USB-C'],
                    ['group' => 'Certifications','label' => 'Certified',     'value' => 'Apple MFi Certified'],
                ],
            ],

            // ── 19. Baseus 100W GaN Charger ───────────────────────────────────
            [
                'sku'               => 'BAS-100W',
                'name'              => 'Baseus 100W GaN Charger (4-Port)',
                'img'               => 'charger,usb,technology',
                'category'          => 'Chargers',
                'brand'             => 'Baseus',
                'cost_price'        => 25.00,
                'selling_price'     => 45.99,
                'compare_price'     => 59.99,
                'stock'             => 80,
                'weight'            => 0.155,
                'is_new_arrival'    => true,
                'short_description' => '100 W total with 4 ports — charge a laptop, phone, tablet, and earbuds at once.',
                'description'       => '<p>Baseus 100W GaN Charger features <strong>4 charging ports</strong> (2× USB-C + 2× USB-A) delivering a combined 100 W, with intelligent power distribution ensuring every device charges at its optimal rate.</p>',
                'tags'              => ['fast-charging', 'usb-c', 'new-arrival', 'accessories'],
                'seo' => [
                    'meta_title'       => 'Baseus 100W GaN Charger — Buy Online | Mobile Shop',
                    'meta_description' => 'Buy Baseus 100W 4-port GaN charger. Charge laptop, phone, tablet, and earbuds simultaneously.',
                    'meta_keywords'    => 'Baseus 100W charger, GaN charger, 4 port charger, USB-C fast charger',
                ],
                'specs' => [
                    ['group' => 'Power',        'label' => 'Total Output',   'value' => '100 W'],
                    ['group' => 'Power',        'label' => 'USB-C 1',        'value' => 'Up to 100 W (single port)'],
                    ['group' => 'Power',        'label' => 'USB-C 2',        'value' => 'Up to 30 W'],
                    ['group' => 'Power',        'label' => 'USB-A 1 & 2',    'value' => 'Up to 22.5 W each'],
                    ['group' => 'Technology',   'label' => 'Technology',     'value' => 'GaN'],
                    ['group' => 'Safety',       'label' => 'Protection',     'value' => 'Over-voltage, over-current, over-temperature'],
                ],
            ],

            // ── 20. Belkin MagSafe 3-in-1 Charger ────────────────────────────
            [
                'sku'               => 'BLK-MS3IN1',
                'name'              => 'Belkin 3-in-1 MagSafe Wireless Charger',
                'img'               => 'wireless,charger,magsafe',
                'category'          => 'Chargers',
                'brand'             => 'Belkin',
                'cost_price'        => 90.00,
                'selling_price'     => 149.99,
                'compare_price'     => 179.99,
                'stock'             => 40,
                'weight'            => 0.280,
                'is_featured'       => true,
                'short_description' => 'Charge iPhone, Apple Watch, and AirPods simultaneously with MagSafe-certified 15 W wireless.',
                'description'       => '<p>The Belkin 3-in-1 MagSafe Wireless Charger lets you charge your <strong>iPhone at full 15 W MagSafe speed</strong>, Apple Watch, and AirPods — all at the same time from a single elegant stand.</p>',
                'tags'              => ['apple', 'magsafe', 'wireless', 'fast-charging', 'featured', 'accessories'],
                'seo' => [
                    'meta_title'       => 'Belkin 3-in-1 MagSafe Charger — Buy Online | Mobile Shop',
                    'meta_description' => 'Buy Belkin 3-in-1 MagSafe Wireless Charger. Charge iPhone, Apple Watch, and AirPods simultaneously.',
                    'meta_keywords'    => 'Belkin MagSafe charger, 3-in-1 wireless charger, MagSafe stand, buy wireless charger',
                ],
                'specs' => [
                    ['group' => 'Power',        'label' => 'iPhone Charging', 'value' => '15 W MagSafe'],
                    ['group' => 'Power',        'label' => 'Apple Watch',     'value' => 'Fast charging'],
                    ['group' => 'Power',        'label' => 'AirPods',         'value' => '5 W Qi wireless'],
                    ['group' => 'Compatibility','label' => 'iPhone',          'value' => 'iPhone 12 and later (MagSafe)'],
                    ['group' => 'Compatibility','label' => 'Apple Watch',     'value' => 'Series 4 and later'],
                    ['group' => 'Build',        'label' => 'Certification',   'value' => 'Apple MFi Certified'],
                ],
            ],

        ]; // end catalog()
    }
}
