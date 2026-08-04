<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $faqs = json_encode([
            ['q' => 'What is your return policy?',          'a' => 'We offer a 30-day hassle-free return policy on all products. Items must be in their original condition with all accessories and packaging included.'],
            ['q' => 'How long does delivery take?',         'a' => 'Standard delivery takes 2–5 business days. Same-day delivery is available in select areas for orders placed before 2 PM.'],
            ['q' => 'Do products come with a warranty?',    'a' => 'Yes! All our products come with the manufacturer\'s warranty. Smartphones and tablets carry a 1-year warranty, accessories carry 6 months.'],
            ['q' => 'How can I track my order?',            'a' => 'Once your order ships you\'ll receive a tracking number by email. You can also track from your account dashboard under \'My Orders\'.'],
            ['q' => 'What payment methods do you accept?',  'a' => 'We accept all major cards (Visa, Mastercard, Amex), mobile banking, cash on delivery, and bank transfers. All payments are SSL-protected.'],
            ['q' => 'Do you have a physical store?',        'a' => 'Yes! You\'re welcome to visit us during working hours. Our address and hours are listed on the contact page.'],
        ]);

        DB::table('store_settings')->insertOrIgnore([
            'key'        => 'faq_items',
            'group'      => 'faq',
            'type'       => 'json',
            'label'      => 'FAQ Items',
            'value'      => $faqs,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('store_settings')->where('key', 'faq_items')->delete();
    }
};
