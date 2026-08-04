<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->char('uuid', 36)->nullable()->unique()->after('id');
            $table->enum('type', ['simple', 'variable'])->default('simple')->after('uuid');
            $table->string('status', 20)->default('draft')->after('type');
            $table->string('slug', 300)->nullable()->unique()->after('name');
            $table->text('short_description')->nullable()->after('description');
            $table->decimal('compare_price', 12, 2)->nullable()->after('selling_price');
            $table->decimal('weight', 8, 3)->nullable()->after('compare_price');
            $table->string('weight_unit', 10)->default('kg')->after('weight');
            $table->decimal('length', 8, 2)->nullable()->after('weight_unit');
            $table->decimal('width', 8, 2)->nullable()->after('length');
            $table->decimal('height', 8, 2)->nullable()->after('width');
            $table->boolean('is_featured')->default(false)->after('is_active');
            $table->boolean('is_digital')->default(false)->after('is_featured');
            $table->unsignedInteger('sort_order')->default(0)->after('is_digital');
            $table->timestamp('published_at')->nullable()->after('sort_order');

            $table->index('status');
            $table->index('type');
            $table->index('is_featured');
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['type']);
            $table->dropIndex(['is_featured']);
            $table->dropIndex(['published_at']);
            $table->dropColumn([
                'uuid', 'type', 'status', 'slug', 'short_description',
                'compare_price', 'weight', 'weight_unit',
                'length', 'width', 'height',
                'is_featured', 'is_digital', 'sort_order', 'published_at',
            ]);
        });
    }
};
