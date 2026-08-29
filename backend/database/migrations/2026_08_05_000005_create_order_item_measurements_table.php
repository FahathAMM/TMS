<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_item_measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('measurement_field_id')->constrained();
            $table->decimal('value', 6, 2)->nullable();
            $table->timestamps();

            $table->unique(['order_item_id', 'measurement_field_id'], 'order_item_measurements_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_measurements');
    }
};
