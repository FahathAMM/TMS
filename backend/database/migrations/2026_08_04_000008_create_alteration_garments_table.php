<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alteration_garments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alteration_order_id')->constrained()->cascadeOnDelete();
            $table->string('garment_type');
            $table->string('description')->nullable();
            $table->string('tag_number')->unique();
            $table->unsignedInteger('quantity')->default(1);
            $table->string('status')->default('pending'); // pending, in_progress, ready, delivered
            $table->boolean('measurements_required')->default(false);
            $table->text('notes')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['alteration_order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alteration_garments');
    }
};
