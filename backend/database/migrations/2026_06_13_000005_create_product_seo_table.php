<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_seo', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->unique();
            $table->string('meta_title', 160)->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->string('meta_keywords', 500)->nullable();
            $table->string('og_title', 160)->nullable();
            $table->string('og_description', 320)->nullable();
            $table->string('og_image', 500)->nullable();
            $table->string('canonical_url', 500)->nullable();
            $table->json('schema_markup')->nullable();
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_seo');
    }
};
