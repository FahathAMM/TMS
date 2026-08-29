<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alteration_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alteration_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('alteration_garment_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('alteration_task_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['alteration_order_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alteration_status_history');
    }
};
