<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('variant_id')
                ->nullable()
                ->constrained('product_variants')
                ->nullOnDelete();
            $table->string('product_name');       // snapshot
            $table->string('product_sku', 100)->nullable(); // snapshot
            $table->decimal('quantity', 10, 3);
            $table->decimal('cost_price', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();

            $table->index('supplier_return_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_return_items');
    }
};
