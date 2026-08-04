<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $rows = [

            // ── Hero ──────────────────────────────────────────────────────────
            ['key' => 'about_hero_is_active', 'type' => 'boolean',  'label' => 'Show Hero Section',         'value' => '1',           'sort_order' => 1],
            ['key' => 'about_hero_title',     'type' => 'text',     'label' => 'Hero Title',                'value' => 'About Us',    'sort_order' => 2],
            ['key' => 'about_hero_subtitle',  'type' => 'textarea', 'label' => 'Hero Subtitle',             'value' => 'We are a passionate team dedicated to bringing you the best mobile technology at unbeatable prices.', 'sort_order' => 3],
            ['key' => 'about_hero_image',     'type' => 'image',    'label' => 'Hero Background Image',     'value' => null,          'sort_order' => 4],

            // ── Company Overview ──────────────────────────────────────────────
            ['key' => 'about_overview_is_active',      'type' => 'boolean',  'label' => 'Show Overview Section', 'value' => '1', 'sort_order' => 10],
            ['key' => 'about_overview_description',    'type' => 'textarea', 'label' => 'Company Description',   'value' => 'Founded with a vision to make cutting-edge technology accessible to everyone, we have grown into a trusted destination for smartphones, tablets, and accessories. Our commitment to quality, authenticity, and customer satisfaction drives everything we do.', 'sort_order' => 11],
            ['key' => 'about_overview_mission_title',  'type' => 'text',     'label' => 'Mission Title',         'value' => 'Our Mission', 'sort_order' => 12],
            ['key' => 'about_overview_mission_text',   'type' => 'textarea', 'label' => 'Mission Text',          'value' => 'To provide authentic, high-quality mobile technology to every customer with exceptional service, competitive pricing, and a seamless shopping experience.', 'sort_order' => 13],
            ['key' => 'about_overview_vision_title',   'type' => 'text',     'label' => 'Vision Title',          'value' => 'Our Vision',  'sort_order' => 14],
            ['key' => 'about_overview_vision_text',    'type' => 'textarea', 'label' => 'Vision Text',           'value' => 'To become the most trusted and customer-centric mobile technology retailer, recognized for product quality, innovation, and outstanding service.', 'sort_order' => 15],
            ['key' => 'about_overview_values',         'type' => 'json',     'label' => 'Core Values',           'value' => json_encode([
                ['id' => 1, 'icon' => 'Shield',       'color' => 'blue',    'title' => 'Integrity',     'description' => 'Honest pricing, authentic products, and transparent business practices.'],
                ['id' => 2, 'icon' => 'Star',         'color' => 'amber',   'title' => 'Excellence',    'description' => 'We never compromise on quality — from our products to our customer service.'],
                ['id' => 3, 'icon' => 'Heart',        'color' => 'rose',    'title' => 'Customer First','description' => 'Every decision starts with asking: is this the best for our customers?'],
                ['id' => 4, 'icon' => 'Zap',          'color' => 'violet',  'title' => 'Innovation',    'description' => 'Continuously improving our platform, products, and processes.'],
            ]), 'sort_order' => 16],

            // ── Services ──────────────────────────────────────────────────────
            ['key' => 'about_services_is_active', 'type' => 'boolean',  'label' => 'Show Services Section', 'value' => '1',              'sort_order' => 20],
            ['key' => 'about_services_title',     'type' => 'text',     'label' => 'Services Title',        'value' => 'What We Offer',  'sort_order' => 21],
            ['key' => 'about_services_subtitle',  'type' => 'textarea', 'label' => 'Services Subtitle',     'value' => 'From in-store POS to full e-commerce — end-to-end solutions for modern retail.', 'sort_order' => 22],
            ['key' => 'about_services_items',     'type' => 'json',     'label' => 'Services List',         'value' => json_encode([
                ['id' => 1, 'is_active' => true, 'icon' => 'Monitor',     'color' => 'blue',    'title' => 'POS System',             'description' => 'Fast, reliable point-of-sale system for in-store transactions with real-time inventory.'],
                ['id' => 2, 'is_active' => true, 'icon' => 'ShoppingBag', 'color' => 'emerald', 'title' => 'E-Commerce Platform',    'description' => 'Fully featured online storefront with product browsing, cart, checkout, and order tracking.'],
                ['id' => 3, 'is_active' => true, 'icon' => 'Package',     'color' => 'violet',  'title' => 'Inventory Management',   'description' => 'Real-time stock tracking, low-stock alerts, purchase orders, and multi-location support.'],
                ['id' => 4, 'is_active' => true, 'icon' => 'BarChart2',   'color' => 'amber',   'title' => 'Sales & Reporting',      'description' => 'Comprehensive dashboards to track revenue, top products, and business performance.'],
                ['id' => 5, 'is_active' => true, 'icon' => 'Users',       'color' => 'rose',    'title' => 'Customer Management',    'description' => 'Manage customer profiles, order history, and loyalty to build lasting relationships.'],
                ['id' => 6, 'is_active' => true, 'icon' => 'Building2',   'color' => 'cyan',    'title' => 'Multi-Branch Support',   'description' => 'Manage multiple locations from a single dashboard with role-based access control.'],
            ]), 'sort_order' => 23],

            // ── Statistics ────────────────────────────────────────────────────
            ['key' => 'about_stats_is_active', 'type' => 'boolean', 'label' => 'Show Statistics Section', 'value' => '1', 'sort_order' => 30],
            ['key' => 'about_stats_items',     'type' => 'json',    'label' => 'Statistics',              'value' => json_encode([
                ['id' => 1, 'icon' => 'Calendar',     'value' => '5+',   'label' => 'Years of Experience'],
                ['id' => 2, 'icon' => 'Package',      'value' => '500+', 'label' => 'Products Available'],
                ['id' => 3, 'icon' => 'Users',        'value' => '10K+', 'label' => 'Happy Customers'],
                ['id' => 4, 'icon' => 'ShoppingCart', 'value' => '25K+', 'label' => 'Orders Completed'],
                ['id' => 5, 'icon' => 'Building2',    'value' => '3+',   'label' => 'Branches Served'],
            ]), 'sort_order' => 31],

            // ── Team ──────────────────────────────────────────────────────────
            ['key' => 'about_team_is_active', 'type' => 'boolean',  'label' => 'Show Team Section',  'value' => '1',                     'sort_order' => 40],
            ['key' => 'about_team_title',     'type' => 'text',     'label' => 'Team Section Title', 'value' => 'Meet Our Team',         'sort_order' => 41],
            ['key' => 'about_team_subtitle',  'type' => 'textarea', 'label' => 'Team Subtitle',      'value' => 'The passionate people behind our success.', 'sort_order' => 42],
            ['key' => 'about_team_members',   'type' => 'json',     'label' => 'Team Members',       'value' => json_encode([]),         'sort_order' => 43],

            // ── CTA ───────────────────────────────────────────────────────────
            ['key' => 'about_cta_is_active',    'type' => 'boolean',  'label' => 'Show CTA Section',      'value' => '1',              'sort_order' => 50],
            ['key' => 'about_cta_title',        'type' => 'text',     'label' => 'CTA Title',             'value' => 'Ready to Get Started?', 'sort_order' => 51],
            ['key' => 'about_cta_subtitle',     'type' => 'textarea', 'label' => 'CTA Subtitle',         'value' => 'Browse our full catalog of authentic mobile devices and accessories, or get in touch with our team.', 'sort_order' => 52],
            ['key' => 'about_cta_shop_label',   'type' => 'text',     'label' => 'Shop Button Label',     'value' => 'Shop Now',       'sort_order' => 53],
            ['key' => 'about_cta_contact_label','type' => 'text',     'label' => 'Contact Button Label',  'value' => 'Contact Us',     'sort_order' => 54],

            // ── SEO ───────────────────────────────────────────────────────────
            ['key' => 'about_seo_title',       'type' => 'text',     'label' => 'SEO Meta Title',       'value' => 'About Us — Mobile Shop', 'sort_order' => 60],
            ['key' => 'about_seo_description', 'type' => 'textarea', 'label' => 'SEO Meta Description', 'value' => 'Learn about our story, mission, team, and commitment to bringing you the best mobile technology.', 'sort_order' => 61],
            ['key' => 'about_seo_keywords',    'type' => 'text',     'label' => 'SEO Keywords',         'value' => 'about mobile shop, our story, mobile store team, mission vision', 'sort_order' => 62],
        ];

        foreach ($rows as $row) {
            DB::table('store_settings')->insertOrIgnore(array_merge($row, [
                'group'      => 'about',
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        $keys = DB::table('store_settings')->where('group', 'about')->pluck('key')->toArray();
        DB::table('store_settings')->whereIn('key', $keys)->delete();
    }
};
