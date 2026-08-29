<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Dev/demo seed data only at this stage — safe to reset rather than
        // attempt an in-place transform of category-grouped rows into types.
        // customer_measurements / alteration_garment_measurements still hold
        // FK references to this table at this point in the migration order,
        // so FK checks must be relaxed for the truncate to succeed.
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('measurement_types')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        Schema::table('measurement_types', function (Blueprint $table) {
            // 'unit' now lives per-field on measurement_fields (different
            // points on the same garment can use different units).
            $table->dropColumn(['category', 'unit']);
            $table->text('description')->nullable()->after('name');
            $table->string('image')->nullable()->after('description');
            $table->boolean('is_active')->default(true)->after('image');
        });
    }

    public function down(): void
    {
        Schema::table('measurement_types', function (Blueprint $table) {
            $table->dropColumn(['description', 'image', 'is_active']);
            $table->string('category')->after('name');
            $table->string('unit', 10)->default('inches')->after('category');
        });
    }
};
