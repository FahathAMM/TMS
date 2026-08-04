<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $cards = json_encode([
            ['id' => 1, 'is_active' => true, 'icon' => 'Truck',        'color' => 'blue',    'title' => 'Fast Delivery',         'description' => 'Same-day dispatch on orders placed before 3 PM. Real-time tracking from warehouse to doorstep.'],
            ['id' => 2, 'is_active' => true, 'icon' => 'ShieldCheck',  'color' => 'emerald', 'title' => 'Secure Payments',        'description' => '256-bit SSL encryption on all transactions. Pay with card, bank transfer, or cash on delivery.'],
            ['id' => 3, 'is_active' => true, 'icon' => 'RefreshCw',    'color' => 'violet',  'title' => 'Easy Returns',           'description' => '30-day hassle-free returns on all products. Simply initiate a return and we handle the rest.'],
            ['id' => 4, 'is_active' => true, 'icon' => 'Headphones',   'color' => 'amber',   'title' => '24/7 Support',           'description' => 'Live chat, email, and phone support around the clock. Our team responds in under 2 minutes.'],
            ['id' => 5, 'is_active' => true, 'icon' => 'CreditCard',   'color' => 'rose',    'title' => 'Best Price Guarantee',   'description' => 'Found it cheaper elsewhere? We\'ll match it, no questions asked. Quality at the best price.'],
            ['id' => 6, 'is_active' => true, 'icon' => 'Package',      'color' => 'cyan',    'title' => '100% Genuine',           'description' => 'Every product is sourced directly from authorized distributors. Authenticity guaranteed.'],
        ]);

        $rows = [
            ['key' => 'why_us_title',    'group' => 'why_us', 'type' => 'text',     'label' => 'Section Title',    'value' => 'Why Shop With Us?',                                                                          'sort_order' => 1],
            ['key' => 'why_us_subtitle', 'group' => 'why_us', 'type' => 'textarea', 'label' => 'Section Subtitle', 'value' => "We're committed to giving you the best shopping experience from browse to delivery and beyond.", 'sort_order' => 2],
            ['key' => 'why_us_cards',    'group' => 'why_us', 'type' => 'json',     'label' => 'Feature Cards',    'value' => $cards,                                                                                        'sort_order' => 3],
        ];

        foreach ($rows as $row) {
            DB::table('store_settings')->insertOrIgnore(array_merge($row, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        DB::table('store_settings')->whereIn('key', ['why_us_title', 'why_us_subtitle', 'why_us_cards'])->delete();
    }
};
