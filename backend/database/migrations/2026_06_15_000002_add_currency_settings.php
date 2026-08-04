<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Remove the basic currency keys from general group — they move to currency group
        DB::table('store_settings')
            ->whereIn('key', ['currency_symbol', 'currency_code'])
            ->where('group', 'general')
            ->delete();

        $now      = now();
        $settings = [
            ['key' => 'currency_code',      'group' => 'currency', 'type' => 'text', 'label' => 'Currency Code',       'value' => 'USD',  'sort_order' => 1],
            ['key' => 'currency_symbol',    'group' => 'currency', 'type' => 'text', 'label' => 'Currency Symbol',     'value' => '$',    'sort_order' => 2],
            ['key' => 'currency_position',  'group' => 'currency', 'type' => 'text', 'label' => 'Symbol Position',     'value' => 'left', 'sort_order' => 3],
            ['key' => 'decimal_places',     'group' => 'currency', 'type' => 'text', 'label' => 'Decimal Places',      'value' => '2',    'sort_order' => 4],
            ['key' => 'decimal_separator',  'group' => 'currency', 'type' => 'text', 'label' => 'Decimal Separator',   'value' => '.',    'sort_order' => 5],
            ['key' => 'thousand_separator', 'group' => 'currency', 'type' => 'text', 'label' => 'Thousand Separator',  'value' => ',',    'sort_order' => 6],
        ];

        foreach ($settings as $row) {
            DB::table('store_settings')->updateOrInsert(
                ['key' => $row['key']],
                array_merge($row, ['created_at' => $now, 'updated_at' => $now])
            );
        }

        Cache::forget('store_settings:all');
    }

    public function down(): void
    {
        DB::table('store_settings')->where('group', 'currency')->delete();

        $now = now();
        DB::table('store_settings')->insertOrIgnore([
            ['key' => 'currency_symbol', 'group' => 'general', 'type' => 'text', 'label' => 'Currency Symbol', 'value' => '$',   'sort_order' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'currency_code',   'group' => 'general', 'type' => 'text', 'label' => 'Currency Code',   'value' => 'USD', 'sort_order' => 5, 'created_at' => $now, 'updated_at' => $now],
        ]);

        Cache::forget('store_settings:all');
    }
};
