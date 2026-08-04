<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE stock_movements MODIFY type ENUM(
            'purchase_in',
            'sale_out',
            'supplier_return_out',
            'customer_return_in',
            'adjustment_in',
            'adjustment_out',
            'opening_stock',
            'material_consumed',
            'material_returned',
            'waste_scrap'
        ) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE stock_movements MODIFY type ENUM(
            'purchase_in',
            'sale_out',
            'supplier_return_out',
            'customer_return_in',
            'adjustment_in',
            'adjustment_out',
            'opening_stock'
        ) NOT NULL");
    }
};
