<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (! Schema::hasColumn('categories', 'parent_id')) {
                $table->unsignedBigInteger('parent_id')->nullable()->after('id');
            }
            if (! Schema::hasColumn('categories', 'description')) {
                $table->text('description')->nullable();
            }
            if (! Schema::hasColumn('categories', 'image')) {
                $table->string('image', 500)->nullable()->after('description');
            }
            if (! Schema::hasColumn('categories', 'path')) {
                $table->string('path', 750)->nullable()->after('image');
            }
            if (! Schema::hasColumn('categories', 'depth')) {
                $table->unsignedTinyInteger('depth')->default(0)->after('path');
            }
            if (! Schema::hasColumn('categories', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('depth');
            }
            if (! Schema::hasColumn('categories', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
        });

        // Add foreign key only if it doesn't exist
        $fkExists = collect(DB::select("
            SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'categories'
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            AND CONSTRAINT_NAME = 'categories_parent_id_foreign'
        "))->isNotEmpty();

        if (! $fkExists) {
            Schema::table('categories', function (Blueprint $table) {
                $table->foreign('parent_id')
                      ->references('id')
                      ->on('categories')
                      ->nullOnDelete();
            });
        }

        // Add individual indexes only if they don't exist
        $existingIndexes = collect(Schema::getIndexes('categories'))->pluck('name');

        if (! $existingIndexes->contains('categories_parent_id_index')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->index('parent_id');
            });
        }

        // Use prefix index for path to stay within MySQL's 3072 byte key limit (utf8mb4: 4 bytes/char)
        if (! $existingIndexes->contains('categories_path_index')) {
            DB::statement('ALTER TABLE categories ADD INDEX categories_path_index (path(750))');
        }

        if (! $existingIndexes->contains('categories_depth_index')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->index('depth');
            });
        }

        if (! $existingIndexes->contains('categories_sort_order_index')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->index('sort_order');
            });
        }
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropIndex(['parent_id']);
            $table->dropIndex(['path']);
            $table->dropIndex(['depth']);
            $table->dropIndex(['sort_order']);
            $table->dropColumn(['parent_id', 'image', 'path', 'depth', 'sort_order']);
        });
    }
};
