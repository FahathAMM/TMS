<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FabricCatalogSeeder extends Seeder
{
    /**
     * Replaces the leftover Mobile Shop POS electronics catalogue with a real
     * fabric & trim inventory for a tailor shop. Two products (ids 46 and 51
     * in the original seed) are RESTRICT-referenced by order_item_materials
     * for an existing sample order, so they're repurposed in place via
     * updateOrCreate(sku) rather than deleted+recreated.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $this->purgeElectronicsCatalog();

            $categories = $this->seedCategories();
            $brands     = $this->seedBrands();
            $this->seedProducts($categories, $brands);

            // Only safe now that every surviving product points at a new
            // category/brand — category_id is RESTRICT-on-delete.
            Category::whereNotIn('slug', array_keys($categories))->get()->each->delete();
            Brand::whereNotIn('name', array_keys($brands))->delete();
        });
    }

    private function purgeElectronicsCatalog(): void
    {
        // Keep any product still referenced by a RESTRICT-on-delete foreign key
        // (order_item_materials / sale_items / stock_adjustments) — those are
        // repurposed below instead of deleted.
        $keepIds = DB::table('order_item_materials')->distinct()->pluck('product_id')
            ->merge(DB::table('sale_items')->distinct()->pluck('product_id'))
            ->merge(DB::table('stock_adjustments')->distinct()->pluck('product_id'))
            ->unique()->values();

        // Product uses SoftDeletes — forceDelete() so the row (and its
        // category_id/brand_id FK references) are actually gone.
        Product::withTrashed()->whereNotIn('id', $keepIds)->get()->each->forceDelete();

        // Old electronics categories/brands are now unreferenced (kept products
        // get reassigned to new categories/brands in seedProducts()) — but
        // delete them AFTER products are reassigned, so do it lazily via name.
    }

    /** @return array<string, Category> keyed by slug */
    private function seedCategories(): array
    {
        $tree = [
            'fabrics' => ['name' => 'Fabrics', 'children' => [
                'cotton'     => 'Cotton',
                'linen'      => 'Linen',
                'wool'       => 'Wool',
                'silk'       => 'Silk',
                'synthetic'  => 'Synthetic & Blends',
                'denim'      => 'Denim',
            ]],
            'trims' => ['name' => 'Trims & Notions', 'children' => [
                'buttons'      => 'Buttons',
                'zippers'      => 'Zippers',
                'thread'       => 'Thread',
                'interfacing'  => 'Interfacing & Lining',
                'elastic-tape' => 'Elastic & Tape',
                'hardware'     => 'Shoulder Pads & Hardware',
            ]],
        ];

        $map = [];
        $sortOrder = 1;

        foreach ($tree as $parentSlug => $parentDef) {
            $parent = Category::updateOrCreate(
                ['slug' => $parentSlug],
                ['name' => $parentDef['name'], 'parent_id' => null, 'sort_order' => $sortOrder++, 'is_active' => true],
            );
            $map[$parentSlug] = $parent;

            $childSort = 1;
            foreach ($parentDef['children'] as $slug => $name) {
                $map[$slug] = Category::updateOrCreate(
                    ['slug' => $slug],
                    ['name' => $name, 'parent_id' => $parent->id, 'sort_order' => $childSort++, 'is_active' => true],
                );
            }
        }

        return $map;
    }

    /** @return array<string, int> brand name => id */
    private function seedBrands(): array
    {
        $names = ['YKK', 'Gütermann', 'Prym', 'Vlieseline'];
        $ids = [];

        foreach ($names as $name) {
            $ids[$name] = Brand::updateOrCreate(['name' => $name], ['is_active' => true])->id;
        }

        return $ids;
    }

    /**
     * @param array<string, Category> $categories
     * @param array<string, int> $brands
     */
    private function seedProducts(array $categories, array $brands): void
    {
        $fabrics = [
            ['sku' => 'FAB-CTN-WHT',     'name' => 'Cotton Poplin - White',              'cat' => 'cotton',    'cost' => 3.50,  'sell' => 6.00,  'stock' => 120],
            ['sku' => 'FAB-CTN-SKY',     'name' => 'Cotton Poplin - Sky Blue',           'cat' => 'cotton',    'cost' => 3.50,  'sell' => 6.00,  'stock' => 95],
            ['sku' => 'FAB-CTN-TWL-KHK', 'name' => 'Cotton Twill - Khaki',               'cat' => 'cotton',    'cost' => 4.20,  'sell' => 7.00,  'stock' => 80],
            ['sku' => 'FAB-LIN-NAT',     'name' => 'Linen - Natural',                    'cat' => 'linen',     'cost' => 8.50,  'sell' => 14.00, 'stock' => 60],
            ['sku' => 'FAB-LIN-CHR',     'name' => 'Linen - Charcoal',                   'cat' => 'linen',     'cost' => 8.50,  'sell' => 14.00, 'stock' => 45],
            ['sku' => 'FAB-WOL-NVY',     'name' => 'Wool Suiting - Navy',                'cat' => 'wool',      'cost' => 22.00, 'sell' => 38.00, 'stock' => 40],
            ['sku' => 'FAB-WOL-CHR',     'name' => 'Wool Suiting - Charcoal Grey',       'cat' => 'wool',      'cost' => 22.00, 'sell' => 38.00, 'stock' => 35],
            ['sku' => 'FAB-WOL-PIN',     'name' => 'Wool Suiting - Black Pinstripe',     'cat' => 'wool',      'cost' => 24.00, 'sell' => 42.00, 'stock' => 25],
            ['sku' => 'FAB-SLK-IVR',     'name' => 'Silk Dupioni - Ivory',               'cat' => 'silk',      'cost' => 18.00, 'sell' => 32.00, 'stock' => 30],
            ['sku' => 'FAB-SLK-BRG',     'name' => 'Silk Charmeuse - Burgundy',          'cat' => 'silk',      'cost' => 20.00, 'sell' => 35.00, 'stock' => 20],
            ['sku' => 'FAB-LNG-POL-BLK', 'name' => 'Polyester Lining - Black',           'cat' => 'synthetic', 'cost' => 2.80,  'sell' => 5.00,  'stock' => 150],
            ['sku' => 'FAB-LNG-POL-NUD', 'name' => 'Polyester Lining - Nude',            'cat' => 'synthetic', 'cost' => 2.80,  'sell' => 5.00,  'stock' => 130],
            ['sku' => 'FAB-DNM-IND',     'name' => 'Denim - Indigo 12oz',                'cat' => 'denim',     'cost' => 6.50,  'sell' => 11.00, 'stock' => 70],
            ['sku' => 'FAB-CHF-BLS',     'name' => 'Chiffon - Blush Pink',               'cat' => 'synthetic', 'cost' => 5.00,  'sell' => 9.00,  'stock' => 55],
            ['sku' => 'FAB-STN-EMR',     'name' => 'Satin - Emerald Green',              'cat' => 'synthetic', 'cost' => 5.50,  'sell' => 9.50,  'stock' => 40],
            ['sku' => 'FAB-GRG-MRN',     'name' => 'Georgette - Maroon',                 'cat' => 'synthetic', 'cost' => 4.80,  'sell' => 8.50,  'stock' => 50],
            ['sku' => 'FAB-CRP-NVY',     'name' => 'Crepe - Navy',                       'cat' => 'synthetic', 'cost' => 6.00,  'sell' => 10.50, 'stock' => 45],
        ];

        $trims = [
            ['sku' => 'TRM-BTN-SHT-WHT', 'name' => 'Shirt Buttons 4-Hole 11mm White (pack of 100)', 'cat' => 'buttons',     'brand' => 'Prym',      'unit' => 'pack',  'cost' => 3.00, 'sell' => 5.50,  'stock' => 40],
            ['sku' => 'TRM-BTN-SUT-HRN', 'name' => 'Suit Buttons 20mm Horn Finish (pack of 50)',    'cat' => 'buttons',     'brand' => 'Prym',      'unit' => 'pack',  'cost' => 6.50, 'sell' => 11.00, 'stock' => 25],
            ['sku' => 'TRM-HK-BAR',      'name' => 'Trouser Hook & Bar Set (pack of 50)',           'cat' => 'buttons',     'brand' => 'Prym',      'unit' => 'pack',  'cost' => 4.00, 'sell' => 7.50,  'stock' => 30],
            ['sku' => 'TRM-ZIP-7-BLK',   'name' => 'Metal Zipper 7" - Black',                       'cat' => 'zippers',     'brand' => 'YKK',       'unit' => 'piece', 'cost' => 0.80, 'sell' => 1.80,  'stock' => 200],
            ['sku' => 'TRM-ZIP-9-BLK',   'name' => 'Metal Zipper 9" - Black',                       'cat' => 'zippers',     'brand' => 'YKK',       'unit' => 'piece', 'cost' => 0.90, 'sell' => 2.00,  'stock' => 150],
            ['sku' => 'TRM-ZIP-22-INV',  'name' => 'Invisible Zipper 22" - Assorted',               'cat' => 'zippers',     'brand' => 'YKK',       'unit' => 'piece', 'cost' => 1.20, 'sell' => 2.50,  'stock' => 100],
            ['sku' => 'TRM-THR-1000-BLK','name' => 'Polyester Thread 1000m - Black',                'cat' => 'thread',      'brand' => 'Gütermann', 'unit' => 'spool', 'cost' => 2.50, 'sell' => 4.50,  'stock' => 90],
            ['sku' => 'TRM-THR-1000-WHT','name' => 'Polyester Thread 1000m - White',                'cat' => 'thread',      'brand' => 'Gütermann', 'unit' => 'spool', 'cost' => 2.50, 'sell' => 4.50,  'stock' => 90],
            ['sku' => 'TRM-THR-SLK',     'name' => 'Silk Thread 100m - Assorted',                   'cat' => 'thread',      'brand' => 'Gütermann', 'unit' => 'spool', 'cost' => 3.20, 'sell' => 5.80,  'stock' => 60],
            ['sku' => 'TRM-INT-MED',     'name' => 'Fusible Interfacing - Medium Weight',           'cat' => 'interfacing', 'brand' => 'Vlieseline','unit' => 'meter', 'cost' => 2.00, 'sell' => 3.80,  'stock' => 100],
            ['sku' => 'TRM-SHD-PAD',     'name' => 'Shoulder Pads - Set (S/M/L)',                   'cat' => 'hardware',    'brand' => 'Prym',      'unit' => 'pair',  'cost' => 1.50, 'sell' => 3.00,  'stock' => 60],
            ['sku' => 'TRM-ELS-25',      'name' => 'Elastic Waistband Tape 25mm',                   'cat' => 'elastic-tape','brand' => null,        'unit' => 'meter', 'cost' => 0.60, 'sell' => 1.20,  'stock' => 200],
            ['sku' => 'TRM-BIAS-CTN',    'name' => 'Bias Tape - Cotton 20mm',                       'cat' => 'elastic-tape','brand' => null,        'unit' => 'meter', 'cost' => 0.50, 'sell' => 1.00,  'stock' => 150],
            ['sku' => 'TRM-HKE-TAPE',    'name' => 'Hook & Eye Tape',                                'cat' => 'elastic-tape','brand' => null,        'unit' => 'meter', 'cost' => 1.00, 'sell' => 2.00,  'stock' => 80],
        ];

        // Any products still around after the purge only survived because a
        // RESTRICT-on-delete FK (order_item_materials/sale_items/stock_adjustments
        // on a legacy sample order) still points at them — repurpose them in
        // place, one per catalogue row, so their old category/brand becomes
        // unreferenced and safe to delete below. On a fresh DB there are none
        // of these, and every row below is simply created fresh by SKU.
        $legacyIds = Product::pluck('id')->values();
        $legacyIndex = 0;
        $nextLegacyId = function () use ($legacyIds, &$legacyIndex) {
            return $legacyIndex < $legacyIds->count() ? $legacyIds[$legacyIndex++] : null;
        };

        foreach ($fabrics as $row) {
            $this->upsertProduct($row, $categories[$row['cat']]->id, null, 'meter', $nextLegacyId());
        }

        foreach ($trims as $row) {
            $brandId = $row['brand'] ? ($brands[$row['brand']] ?? null) : null;
            $this->upsertProduct($row, $categories[$row['cat']]->id, $brandId, $row['unit'], $nextLegacyId());
        }
    }

    private function upsertProduct(array $row, int $categoryId, ?int $brandId, string $unit, ?int $forceId): void
    {
        $attrs = [
            'name'                => $row['name'],
            'category_id'         => $categoryId,
            'brand_id'            => $brandId,
            'type'                => 'simple',
            'status'              => 'active',
            'is_active'           => true,
            'cost_price'          => $row['cost'],
            'selling_price'       => $row['sell'],
            'stock_quantity'      => $row['stock'],
            'low_stock_threshold' => max(5, (int) round($row['stock'] * 0.15)),
            'unit_of_measure'     => $unit,
        ];

        $product = $forceId ? Product::find($forceId) : null;

        if ($product) {
            $product->fill(array_merge($attrs, ['sku' => $row['sku']]))->save();
            return;
        }

        Product::updateOrCreate(['sku' => $row['sku']], $attrs);
    }
}
