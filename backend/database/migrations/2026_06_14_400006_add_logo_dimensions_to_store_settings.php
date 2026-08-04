<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $rows = [
            [
                'key'        => 'logo_width',
                'group'      => 'media',
                'type'       => 'number',
                'label'      => 'Logo Width (px)',
                'value'      => '120',
                'sort_order' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key'        => 'logo_height',
                'group'      => 'media',
                'type'       => 'number',
                'label'      => 'Logo Height (px)',
                'value'      => '40',
                'sort_order' => 6,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($rows as $row) {
            DB::table('store_settings')->insertOrIgnore($row);
        }
    }

    public function down(): void
    {
        DB::table('store_settings')->whereIn('key', ['logo_width', 'logo_height'])->delete();
    }
};
