<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alteration_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alteration_garment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('alteration_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description'); // snapshot label, e.g. "Hem Trousers" or a custom task
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('quantity')->default(1);
            $table->string('status')->default('pending'); // pending, in_progress, completed
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['alteration_garment_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alteration_tasks');
    }
};
