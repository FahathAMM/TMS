<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Publish all existing draft products immediately
        DB::table('products')->where('status', 'draft')->update(['status' => 'active']);

        // Change column default so new products without explicit status are active
        Schema::table('products', function (Blueprint $table) {
            $table->string('status', 20)->default('active')->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('status', 20)->default('draft')->change();
        });
    }
};
