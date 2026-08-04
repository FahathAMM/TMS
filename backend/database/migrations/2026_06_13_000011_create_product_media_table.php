<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_media', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->string('type', 10)->default('image');
            $table->string('file_name', 255);
            $table->string('file_path', 500);
            $table->string('file_url', 500);
            $table->string('thumbnail_url', 500)->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('alt', 255)->nullable();
            $table->string('title', 255)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('variant_id')->references('id')->on('product_variants')->cascadeOnDelete();

            $table->index('product_id');
            $table->index('variant_id');
            $table->index('type');
            $table->index('sort_order');
            $table->index('is_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_media');
    }
};
