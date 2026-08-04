<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('sku', 100)->unique();
            $table->decimal('price', 12, 2);
            $table->decimal('compare_price', 12, 2)->nullable();
            $table->decimal('cost_price', 12, 2)->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->integer('low_stock_threshold')->default(5);
            $table->decimal('weight', 8, 3)->nullable();
            $table->string('image', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();

            $table->index('product_id');
            $table->index('sku');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
