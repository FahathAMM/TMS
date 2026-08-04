<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_attributes', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('attribute_id');

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('attribute_id')->references('id')->on('attributes')->cascadeOnDelete();

            $table->primary(['product_id', 'attribute_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_attributes');
    }
};
