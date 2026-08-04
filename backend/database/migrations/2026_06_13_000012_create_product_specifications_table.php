<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_specifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('group', 100)->nullable();
            $table->string('label', 200);
            $table->text('value');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();

            $table->index('product_id');
            $table->index('group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_specifications');
    }
};
