<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alteration_garment_measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alteration_garment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('measurement_type_id')->constrained();
            $table->decimal('current_value', 6, 2)->nullable();
            $table->decimal('target_value', 6, 2)->nullable();
            $table->timestamps();

            $table->unique(['alteration_garment_id', 'measurement_type_id'], 'alt_garment_measurement_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alteration_garment_measurements');
    }
};
