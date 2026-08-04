<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 150)->nullable();
            $table->tinyInteger('rating')->unsigned(); // 1–5
            $table->string('title', 200)->nullable();
            $table->text('message');
            $table->string('product_name', 200)->nullable();
            $table->string('avatar', 500)->nullable(); // storage path
            $table->string('status', 20)->default('pending'); // pending|approved|rejected
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
