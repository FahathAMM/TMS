<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Drops the POS / storefront tables the tailoring system never uses: the sales
 * and returns ledger, the shopping cart, offers, the contact form, and the
 * e-commerce product enrichment stack (variants, media, seo, specifications,
 * attributes, tags).
 *
 * Not reversible: down() only restores the foreign-key columns on surviving
 * tables, since recreating twenty-two dropped tables from scratch here would
 * duplicate their original migrations.
 */
return new class extends Migration
{
    /** Child tables first — each holds a foreign key into the one after it. */
    private const TABLES = [
        'sale_return_items',
        'sale_returns',
        'sale_items',
        'sales',
        'supplier_return_items',
        'supplier_returns',
        'offer_products',
        'offers',
        'carts',
        'contact_messages',
        'customer_password_resets',
        'product_variant_values',
        'product_media',
        'product_variants',
        'product_seo',
        'product_specifications',
        'product_attributes',
        'attribute_values',
        'attributes',
        'product_tag',
        'tags',
    ];

    /** Surviving tables that point into the dropped ones. */
    private const FOREIGN_KEYS = [
        ['order_items', 'variant_id', 'order_items_variant_id_foreign'],
        ['purchase_items', 'variant_id', 'purchase_items_variant_id_foreign'],
        ['stock_movements', 'variant_id', 'stock_movements_variant_id_foreign'],
        ['stock_adjustments', 'sale_id', 'stock_adjustments_sale_id_foreign'],
    ];

    public function up(): void
    {
        foreach (self::FOREIGN_KEYS as [$table, $column, $constraint]) {
            if (!Schema::hasColumn($table, $column)) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($constraint, $column) {
                $t->dropForeign($constraint);
                $t->dropColumn($column);
            });
        }

        Schema::disableForeignKeyConstraints();

        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }

        Schema::enableForeignKeyConstraints();

        // The storefront contact form is gone, so its sidebar entry and the
        // permission that gated it would only dangle.
        DB::table('menus')->where('slug', 'contact_messages')->delete();
        DB::table('permissions')->whereIn('name', [
            'view contact_messages',
            'view sales', 'create sales', 'cancel sales',
            'view sale_returns', 'create sale_returns', 'edit sale_returns', 'delete sale_returns',
        ])->delete();
    }

    public function down(): void
    {
        foreach (self::FOREIGN_KEYS as [$table, $column, $constraint]) {
            if (Schema::hasColumn($table, $column)) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($column) {
                $t->unsignedBigInteger($column)->nullable();
            });
        }
    }
};
