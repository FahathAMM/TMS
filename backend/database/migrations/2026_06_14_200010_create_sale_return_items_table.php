<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('variant_id')
                ->nullable()
                ->constrained('product_variants')
                ->nullOnDelete();
            $table->string('product_name');       // snapshot
            $table->string('product_sku', 100)->nullable(); // snapshot
            $table->decimal('quantity', 10, 3);
            $table->decimal('unit_price', 12, 2); // price at time of original sale
            $table->decimal('subtotal', 12, 2);
            $table->string('return_reason')->nullable(); // per-item: damaged, wrong item, etc.
            $table->timestamps();

            $table->index('sale_return_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_return_items');
    }
};
