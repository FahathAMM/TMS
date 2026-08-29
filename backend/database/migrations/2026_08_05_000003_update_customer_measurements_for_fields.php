<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('customer_measurements')->truncate();

        Schema::table('customer_measurements', function (Blueprint $table) {
            $table->dropForeign(['measurement_type_id']);
        });

        // customer_id's FK has no standalone index — it relies on the
        // composite unique() below as its leftmost-prefix support. Give it a
        // plain index first so MySQL will let us drop that unique index.
        Schema::table('customer_measurements', function (Blueprint $table) {
            $table->index('customer_id', 'customer_measurements_customer_id_index');
        });

        Schema::table('customer_measurements', function (Blueprint $table) {
            $table->dropUnique('unique_customer_measurement');
        });

        Schema::table('customer_measurements', function (Blueprint $table) {
            $table->dropColumn('measurement_type_id');
        });

        Schema::table('customer_measurements', function (Blueprint $table) {
            $table->foreignId('measurement_field_id')->after('customer_id')->constrained()->cascadeOnDelete();
            $table->unique(['customer_id', 'measurement_field_id'], 'unique_customer_measurement');
        });

        Schema::table('customer_measurements', function (Blueprint $table) {
            $table->dropIndex('customer_measurements_customer_id_index');
        });
    }

    public function down(): void
    {
        DB::table('customer_measurements')->truncate();

        Schema::table('customer_measurements', function (Blueprint $table) {
            $table->dropForeign(['measurement_field_id']);
        });

        Schema::table('customer_measurements', function (Blueprint $table) {
            $table->index('customer_id', 'customer_measurements_customer_id_index');
        });

        Schema::table('customer_measurements', function (Blueprint $table) {
            $table->dropUnique('unique_customer_measurement');
        });

        Schema::table('customer_measurements', function (Blueprint $table) {
            $table->dropColumn('measurement_field_id');
        });

        Schema::table('customer_measurements', function (Blueprint $table) {
            $table->foreignId('measurement_type_id')->after('customer_id')->constrained()->cascadeOnDelete();
            $table->unique(['customer_id', 'measurement_type_id'], 'unique_customer_measurement');
        });

        Schema::table('customer_measurements', function (Blueprint $table) {
            $table->dropIndex('customer_measurements_customer_id_index');
        });
    }
};
