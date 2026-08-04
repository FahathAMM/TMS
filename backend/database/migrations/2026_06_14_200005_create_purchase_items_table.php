<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('variant_id')
                ->nullable()
                ->constrained('product_variants')
                ->nullOnDelete();
            $table->string('product_name');      // snapshot
            $table->string('product_sku', 100)->nullable(); // snapshot
            $table->decimal('quantity_ordered', 10, 3);
            $table->decimal('quantity_received', 10, 3)->default(0);
            $table->decimal('cost_price', 12, 2);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2); // quantity_received * cost_price
            $table->timestamps();

            $table->index('purchase_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};
