<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('measurement_type_id')->constrained()->cascadeOnDelete();
            $table->decimal('value', 6, 2);
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamps();

            $table->unique(['customer_id', 'measurement_type_id'], 'unique_customer_measurement');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_measurements');
    }
};
