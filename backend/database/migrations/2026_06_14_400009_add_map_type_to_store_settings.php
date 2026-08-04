<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('store_settings')->insertOrIgnore([
            ['key' => 'map_type',         'group' => 'address', 'type' => 'text',     'label' => 'Map Display Type', 'value' => 'none', 'sort_order' => 9,  'created_at' => now(), 'updated_at' => now()],
            ['key' => 'google_map_url',   'group' => 'address', 'type' => 'url',      'label' => 'Google Maps URL',  'value' => null,   'sort_order' => 10, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'google_map_embed', 'group' => 'address', 'type' => 'textarea', 'label' => 'Map Embed Code',   'value' => null,   'sort_order' => 11, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        DB::table('store_settings')->whereIn('key', ['map_type'])->delete();
    }
};
