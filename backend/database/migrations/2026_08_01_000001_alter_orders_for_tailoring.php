<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('order_type')->default('custom_stitching')->after('customer_id');
            $table->date('expected_delivery_date')->nullable()->after('order_type');
            $table->decimal('deposit_amount', 12, 2)->default(0)->after('total_amount');
        });

        // No doctrine/dbal installed — use raw SQL instead of Blueprint::change()
        // to relax NOT NULL on shipping_* (unused for in-store tailoring orders)
        // and to widen the status enum.
        DB::statement("ALTER TABLE orders MODIFY shipping_name VARCHAR(255) NULL");
        DB::statement("ALTER TABLE orders MODIFY shipping_phone VARCHAR(255) NULL");
        DB::statement("ALTER TABLE orders MODIFY shipping_address TEXT NULL");

        DB::statement("ALTER TABLE orders MODIFY status ENUM(
            'pending', 'quoted', 'deposit_paid', 'in_production',
            'ready_for_fitting', 'completed', 'cancelled'
        ) NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE orders MODIFY status ENUM(
            'pending', 'processing', 'shipped', 'completed', 'cancelled'
        ) NOT NULL DEFAULT 'pending'");

        DB::statement("ALTER TABLE orders MODIFY shipping_name VARCHAR(255) NOT NULL");
        DB::statement("ALTER TABLE orders MODIFY shipping_phone VARCHAR(255) NOT NULL");
        DB::statement("ALTER TABLE orders MODIFY shipping_address TEXT NOT NULL");

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['order_type', 'expected_delivery_date', 'deposit_amount']);
        });
    }
};
