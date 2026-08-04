<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attribute_id');
            $table->string('value', 200);
            $table->string('label', 200);
            $table->string('color_code', 7)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('attribute_id')->references('id')->on('attributes')->cascadeOnDelete();
            $table->unique(['attribute_id', 'value']);
            $table->index('attribute_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_values');
    }
};
