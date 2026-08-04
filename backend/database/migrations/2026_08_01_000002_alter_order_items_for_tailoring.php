<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('garment_type')->nullable()->after('order_id');
            $table->string('fabric_source')->nullable()->after('garment_type');
            $table->json('style_specifications')->nullable()->after('product_image');
            $table->string('production_status')->default('pending')->after('style_specifications');
            $table->string('job_card_number')->nullable()->unique()->after('production_status');
            $table->text('qc_notes')->nullable()->after('job_card_number');
            $table->timestamp('qc_passed_at')->nullable()->after('qc_notes');

            $table->index('production_status');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex(['production_status']);
            $table->dropColumn([
                'garment_type', 'fabric_source', 'style_specifications',
                'production_status', 'job_card_number', 'qc_notes', 'qc_passed_at',
            ]);
        });
    }
};
