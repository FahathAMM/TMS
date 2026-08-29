<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alteration_garment_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alteration_garment_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // before, after
            $table->string('path');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['alteration_garment_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alteration_garment_photos');
    }
};
