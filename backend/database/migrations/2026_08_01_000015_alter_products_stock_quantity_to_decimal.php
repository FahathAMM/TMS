<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Fabric/trim stock is measured in fractional units (meters, yards) — an
    // INT column silently rounds every material consumption. Widen to DECIMAL
    // to match stock_movements.quantity (decimal:10,3).
    public function up(): void
    {
        DB::statement('ALTER TABLE products MODIFY stock_quantity DECIMAL(10,3) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE products MODIFY low_stock_threshold DECIMAL(10,3) NOT NULL DEFAULT 5');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE products MODIFY stock_quantity INT NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE products MODIFY low_stock_threshold INT NOT NULL DEFAULT 5');
    }
};
