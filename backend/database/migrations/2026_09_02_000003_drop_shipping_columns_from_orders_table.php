<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'shipping_amount', 'shipping_name', 'shipping_phone',
                'shipping_email', 'shipping_address', 'shipping_city', 'shipping_zip',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('shipping_amount', 12, 2)->default(0)->after('tax_amount');
            $table->string('shipping_name')->nullable()->after('payment_status');
            $table->string('shipping_phone')->nullable()->after('shipping_name');
            $table->string('shipping_email')->nullable()->after('shipping_phone');
            $table->text('shipping_address')->nullable()->after('shipping_email');
            $table->string('shipping_city')->nullable()->after('shipping_address');
            $table->string('shipping_zip')->nullable()->after('shipping_city');
        });
    }
};
