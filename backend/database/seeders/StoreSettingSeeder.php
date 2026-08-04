<?php

namespace Database\Seeders;

use App\Models\StoreSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class StoreSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [

            // ── General ───────────────────────────────────────────────────────
            ['key' => 'store_name',        'group' => 'general', 'type' => 'text',     'label' => 'Store Name',           'value' => 'Mobile Shop',       'sort_order' => 1],
            ['key' => 'store_tagline',     'group' => 'general', 'type' => 'text',     'label' => 'Tagline',              'value' => 'Your trusted mobile destination', 'sort_order' => 2],
            ['key' => 'store_description', 'group' => 'general', 'type' => 'textarea', 'label' => 'Store Description',    'value' => 'Authentic smartphones, tablets, accessories, and electronics at competitive prices with fast delivery.', 'sort_order' => 3],
            ['key' => 'tax_number',        'group' => 'general', 'type' => 'text',     'label' => 'Tax / VAT Number',     'value' => null,                'sort_order' => 6],
            ['key' => 'business_reg',      'group' => 'general', 'type' => 'text',     'label' => 'Business Registration','value' => null,                'sort_order' => 7],
            ['key' => 'new_arrivals_days', 'group' => 'general', 'type' => 'text',     'label' => 'New Arrivals Window (days)', 'value' => '30',           'sort_order' => 8],

            // ── Contact ───────────────────────────────────────────────────────
            ['key' => 'contact_phone',    'group' => 'contact', 'type' => 'phone', 'label' => 'Phone',          'value' => '+1 800 000 0000', 'sort_order' => 1],
            ['key' => 'contact_mobile',   'group' => 'contact', 'type' => 'phone', 'label' => 'Mobile',         'value' => null,              'sort_order' => 2],
            ['key' => 'contact_whatsapp', 'group' => 'contact', 'type' => 'phone', 'label' => 'WhatsApp',       'value' => null,              'sort_order' => 3],
            ['key' => 'contact_email',    'group' => 'contact', 'type' => 'email', 'label' => 'Email',          'value' => 'info@mobileshop.com', 'sort_order' => 4],
            ['key' => 'support_email',    'group' => 'contact', 'type' => 'email', 'label' => 'Support Email',  'value' => 'support@mobileshop.com', 'sort_order' => 5],

            // ── Address ───────────────────────────────────────────────────────
            ['key' => 'address_line_1',  'group' => 'address', 'type' => 'text',     'label' => 'Address Line 1',   'value' => '123 Main Street',  'sort_order' => 1],
            ['key' => 'address_line_2',  'group' => 'address', 'type' => 'text',     'label' => 'Address Line 2',   'value' => null,               'sort_order' => 2],
            ['key' => 'city',            'group' => 'address', 'type' => 'text',     'label' => 'City',             'value' => 'New York',         'sort_order' => 3],
            ['key' => 'state',           'group' => 'address', 'type' => 'text',     'label' => 'State / Region',   'value' => 'NY',               'sort_order' => 4],
            ['key' => 'country',         'group' => 'address', 'type' => 'text',     'label' => 'Country',          'value' => 'United States',    'sort_order' => 5],
            ['key' => 'postal_code',     'group' => 'address', 'type' => 'text',     'label' => 'Postal Code',      'value' => '10001',            'sort_order' => 6],
            ['key' => 'google_map_url',  'group' => 'address', 'type' => 'url',      'label' => 'Google Maps URL',  'value' => null,               'sort_order' => 10],
            ['key' => 'google_map_embed','group' => 'address', 'type' => 'textarea', 'label' => 'Map Embed Code',   'value' => null,               'sort_order' => 11],
            ['key' => 'map_type',        'group' => 'address', 'type' => 'text',     'label' => 'Map Display Type', 'value' => 'none',             'sort_order' => 9],

            // ── Social Media ──────────────────────────────────────────────────
            ['key' => 'social_facebook',  'group' => 'social', 'type' => 'url', 'label' => 'Facebook',  'value' => null, 'sort_order' => 1],
            ['key' => 'social_instagram', 'group' => 'social', 'type' => 'url', 'label' => 'Instagram', 'value' => null, 'sort_order' => 2],
            ['key' => 'social_twitter',   'group' => 'social', 'type' => 'url', 'label' => 'X / Twitter','value' => null, 'sort_order' => 3],
            ['key' => 'social_tiktok',    'group' => 'social', 'type' => 'url', 'label' => 'TikTok',    'value' => null, 'sort_order' => 4],
            ['key' => 'social_linkedin',  'group' => 'social', 'type' => 'url', 'label' => 'LinkedIn',  'value' => null, 'sort_order' => 5],
            ['key' => 'social_youtube',   'group' => 'social', 'type' => 'url', 'label' => 'YouTube',   'value' => null, 'sort_order' => 6],

            // ── Business Hours ────────────────────────────────────────────────
            ['key' => 'hours_opening',        'group' => 'hours', 'type' => 'time',    'label' => 'Opening Time',    'value' => '09:00', 'sort_order' => 1],
            ['key' => 'hours_closing',        'group' => 'hours', 'type' => 'time',    'label' => 'Closing Time',    'value' => '21:00', 'sort_order' => 2],
            ['key' => 'hours_days',           'group' => 'hours', 'type' => 'json',    'label' => 'Working Days',    'value' => '["mon","tue","wed","thu","fri","sat"]', 'sort_order' => 3],
            ['key' => 'hours_is_24h',         'group' => 'hours', 'type' => 'boolean', 'label' => 'Open 24 Hours',   'value' => '0',     'sort_order' => 4],
            ['key' => 'hours_closed_message', 'group' => 'hours', 'type' => 'text',    'label' => 'Closed Message',  'value' => 'We are currently closed. Please visit us during business hours.', 'sort_order' => 5],

            // ── SEO ───────────────────────────────────────────────────────────
            ['key' => 'seo_title',       'group' => 'seo', 'type' => 'text',     'label' => 'Meta Title',       'value' => 'Mobile Shop — Smartphones & Accessories', 'sort_order' => 1],
            ['key' => 'seo_description', 'group' => 'seo', 'type' => 'textarea', 'label' => 'Meta Description', 'value' => 'Your trusted destination for smartphones, tablets, accessories, and electronics.', 'sort_order' => 2],
            ['key' => 'seo_keywords',    'group' => 'seo', 'type' => 'text',     'label' => 'Meta Keywords',    'value' => 'mobile shop, smartphones, accessories, tablets, electronics', 'sort_order' => 3],

            // ── Media ─────────────────────────────────────────────────────────
            ['key' => 'media_logo',         'group' => 'media', 'type' => 'image', 'label' => 'Store Logo',      'value' => null, 'sort_order' => 1],
            ['key' => 'media_favicon',      'group' => 'media', 'type' => 'image', 'label' => 'Favicon',         'value' => null, 'sort_order' => 2],
            ['key' => 'media_og_image',     'group' => 'media', 'type' => 'image', 'label' => 'OG Share Image',  'value' => null, 'sort_order' => 3],
            ['key' => 'media_announcement', 'group' => 'media', 'type' => 'text',   'label' => 'Announcement Bar', 'value' => 'Free shipping on orders over $50 · 30-day easy returns · Genuine products guaranteed', 'sort_order' => 4],
            ['key' => 'logo_width',         'group' => 'media', 'type' => 'number', 'label' => 'Logo Width (px)',  'value' => '120', 'sort_order' => 5],
            ['key' => 'logo_height',        'group' => 'media', 'type' => 'number', 'label' => 'Logo Height (px)', 'value' => '40',  'sort_order' => 6],

            // ── Why Us ────────────────────────────────────────────────────────
            ['key' => 'why_us_title',    'group' => 'why_us', 'type' => 'text',     'label' => 'Section Title',    'value' => 'Why Shop With Us?',                                                                           'sort_order' => 1],
            ['key' => 'why_us_subtitle', 'group' => 'why_us', 'type' => 'textarea', 'label' => 'Section Subtitle', 'value' => "We're committed to giving you the best shopping experience from browse to delivery and beyond.", 'sort_order' => 2],
            ['key' => 'why_us_cards',    'group' => 'why_us', 'type' => 'json',     'label' => 'Feature Cards',    'sort_order' => 3, 'value' => json_encode([
                ['id' => 1, 'is_active' => true, 'icon' => 'Truck',       'color' => 'blue',    'title' => 'Fast Delivery',       'description' => 'Same-day dispatch on orders placed before 3 PM. Real-time tracking from warehouse to doorstep.'],
                ['id' => 2, 'is_active' => true, 'icon' => 'ShieldCheck', 'color' => 'emerald', 'title' => 'Secure Payments',     'description' => '256-bit SSL encryption on all transactions. Pay with card, bank transfer, or cash on delivery.'],
                ['id' => 3, 'is_active' => true, 'icon' => 'RefreshCw',   'color' => 'violet',  'title' => 'Easy Returns',        'description' => '30-day hassle-free returns on all products. Simply initiate a return and we handle the rest.'],
                ['id' => 4, 'is_active' => true, 'icon' => 'Headphones',  'color' => 'amber',   'title' => '24/7 Support',        'description' => 'Live chat, email, and phone support around the clock. Our team responds in under 2 minutes.'],
                ['id' => 5, 'is_active' => true, 'icon' => 'CreditCard',  'color' => 'rose',    'title' => 'Best Price Guarantee','description' => "Found it cheaper elsewhere? We'll match it, no questions asked. Quality at the best price."],
                ['id' => 6, 'is_active' => true, 'icon' => 'Package',     'color' => 'cyan',    'title' => '100% Genuine',        'description' => 'Every product is sourced directly from authorized distributors. Authenticity guaranteed.'],
            ])],

            // ── Banners ───────────────────────────────────────────────────────
            // ── Currency ──────────────────────────────────────────────────────────
            ['key' => 'currency_code',      'group' => 'currency', 'type' => 'text', 'label' => 'Currency Code',      'value' => 'USD',  'sort_order' => 1],
            ['key' => 'currency_symbol',    'group' => 'currency', 'type' => 'text', 'label' => 'Currency Symbol',    'value' => '$',    'sort_order' => 2],
            ['key' => 'currency_position',  'group' => 'currency', 'type' => 'text', 'label' => 'Symbol Position',    'value' => 'left', 'sort_order' => 3],
            ['key' => 'decimal_places',     'group' => 'currency', 'type' => 'text', 'label' => 'Decimal Places',     'value' => '2',    'sort_order' => 4],
            ['key' => 'decimal_separator',  'group' => 'currency', 'type' => 'text', 'label' => 'Decimal Separator',  'value' => '.',    'sort_order' => 5],
            ['key' => 'thousand_separator', 'group' => 'currency', 'type' => 'text', 'label' => 'Thousand Separator', 'value' => ',',    'sort_order' => 6],

            // ── Banners ───────────────────────────────────────────────────────
            ['key' => 'banner_slides', 'group' => 'banners', 'type' => 'json', 'label' => 'Hero Banner Slides', 'sort_order' => 1, 'value' => json_encode([
                [
                    'id' => 1, 'is_active' => true,
                    'badge'           => 'New Arrival 2025',
                    'title'           => 'iPhone 15',
                    'titleAccent'     => 'Pro Max',
                    'subtitle'        => 'Titanium. Brilliant. Pro.',
                    'description'     => 'A17 Pro chip with hardware ray tracing. 48MP camera with 5x optical zoom. USB 3 speeds. The most powerful iPhone ever.',
                    'cta_label'       => 'Shop Now',
                    'cta_href'        => '/shop',
                    'secondary_label' => 'View All Phones',
                    'secondary_href'  => '/shop',
                    'theme'           => 'blue',
                    'icon'            => 'Smartphone',
                    'stat1'           => ['label' => 'Colors',  'value' => '4'],
                    'stat2'           => ['label' => 'Camera',  'value' => '48MP'],
                    'stat3'           => ['label' => 'Battery', 'value' => '25h'],
                ],
                [
                    'id' => 2, 'is_active' => true,
                    'badge'           => 'Flash Sale - Ends Tonight',
                    'title'           => 'Up to 50%',
                    'titleAccent'     => 'Off Today',
                    'subtitle'        => 'All Accessories on Sale',
                    'description'     => 'Cases, chargers, earphones, and cables at unbeatable prices. Premium brands at half the price. Today only!',
                    'cta_label'       => 'Grab Deals',
                    'cta_href'        => '/shop',
                    'secondary_label' => 'All Accessories',
                    'secondary_href'  => '/shop',
                    'theme'           => 'red',
                    'icon'            => 'Zap',
                    'stat1'           => ['label' => 'Savings', 'value' => '50%'],
                    'stat2'           => ['label' => 'Items',   'value' => '500+'],
                    'stat3'           => ['label' => 'Brands',  'value' => '20+'],
                ],
                [
                    'id' => 3, 'is_active' => true,
                    'badge'           => 'Free Shipping Nationwide',
                    'title'           => 'Orders Over',
                    'titleAccent'     => '$50 Ship Free',
                    'subtitle'        => 'Fast & Reliable Delivery',
                    'description'     => 'Same-day dispatch on orders before 3 PM. Real-time tracking from warehouse to your doorstep. Easy returns within 30 days.',
                    'cta_label'       => 'Start Shopping',
                    'cta_href'        => '/shop',
                    'secondary_label' => 'Learn More',
                    'secondary_href'  => '/shop',
                    'theme'           => 'green',
                    'icon'            => 'Headphones',
                    'stat1'           => ['label' => 'Delivery', 'value' => '24h'],
                    'stat2'           => ['label' => 'Returns',  'value' => '30d'],
                    'stat3'           => ['label' => 'Happy',    'value' => '10K+'],
                ],
            ])],
        ];

        foreach ($settings as $setting) {
            StoreSetting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        Cache::forget(\App\Repositories\StoreSettingRepo::CACHE_KEY);
    }
}
