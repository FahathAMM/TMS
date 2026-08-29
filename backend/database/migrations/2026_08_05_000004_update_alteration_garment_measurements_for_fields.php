<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('alteration_garment_measurements')->truncate();

        Schema::table('alteration_garment_measurements', function (Blueprint $table) {
            $table->dropForeign(['measurement_type_id']);
        });

        // alteration_garment_id's FK has no standalone index — it relies on
        // the composite unique() below as its leftmost-prefix support. Give
        // it a plain index first so MySQL will let us drop that unique index.
        Schema::table('alteration_garment_measurements', function (Blueprint $table) {
            $table->index('alteration_garment_id', 'alt_garment_measurements_garment_id_index');
        });

        Schema::table('alteration_garment_measurements', function (Blueprint $table) {
            $table->dropUnique('alt_garment_measurement_unique');
        });

        Schema::table('alteration_garment_measurements', function (Blueprint $table) {
            $table->dropColumn('measurement_type_id');
        });

        Schema::table('alteration_garment_measurements', function (Blueprint $table) {
            $table->foreignId('measurement_field_id')->after('alteration_garment_id')->constrained();
            $table->unique(['alteration_garment_id', 'measurement_field_id'], 'alt_garment_measurement_unique');
        });

        Schema::table('alteration_garment_measurements', function (Blueprint $table) {
            $table->dropIndex('alt_garment_measurements_garment_id_index');
        });
    }

    public function down(): void
    {
        DB::table('alteration_garment_measurements')->truncate();

        Schema::table('alteration_garment_measurements', function (Blueprint $table) {
            $table->dropForeign(['measurement_field_id']);
        });

        Schema::table('alteration_garment_measurements', function (Blueprint $table) {
            $table->index('alteration_garment_id', 'alt_garment_measurements_garment_id_index');
        });

        Schema::table('alteration_garment_measurements', function (Blueprint $table) {
            $table->dropUnique('alt_garment_measurement_unique');
        });

        Schema::table('alteration_garment_measurements', function (Blueprint $table) {
            $table->dropColumn('measurement_field_id');
        });

        Schema::table('alteration_garment_measurements', function (Blueprint $table) {
            $table->foreignId('measurement_type_id')->after('alteration_garment_id')->constrained();
            $table->unique(['alteration_garment_id', 'measurement_type_id'], 'alt_garment_measurement_unique');
        });

        Schema::table('alteration_garment_measurements', function (Blueprint $table) {
            $table->dropIndex('alt_garment_measurements_garment_id_index');
        });
    }
};
