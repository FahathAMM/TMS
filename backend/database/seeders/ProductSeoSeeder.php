<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductSeo;
use Illuminate\Database\Seeder;

class ProductSeoSeeder extends Seeder
{
    public function run(): void
    {
        // Curated SEO for the 25 featured products
        $curated = $this->curatedSeo();

        foreach ($curated as $productName => $seo) {
            $product = Product::where('name', $productName)->first();
            if (!$product) continue;

            ProductSeo::updateOrCreate(
                ['product_id' => $product->id],
                array_merge($seo, ['product_id' => $product->id])
            );
        }

        // Generic SEO for all remaining active products without SEO
        $this->seedGeneric();
    }

    private function seedGeneric(): void
    {
        $products = Product::where('status', 'active')
            ->whereDoesntHave('seo')
            ->get();

        foreach ($products as $product) {
            $title = $product->name . ' – Buy Online';
            $desc  = strip_tags($product->short_description ?? $product->description ?? '');
            $desc  = mb_substr($desc, 0, 155);

            ProductSeo::create([
                'product_id'       => $product->id,
                'meta_title'       => mb_substr($title, 0, 70),
                'meta_description' => $desc ?: 'Shop ' . $product->name . ' at the best price. Fast delivery and genuine product guarantee.',
                'meta_keywords'    => $product->name . ', buy ' . $product->name . ', ' . ($product->category?->name ?? 'electronics'),
                'og_title'         => $title,
                'og_description'   => $desc,
                'og_image'         => null,
                'canonical_url'    => '/products/' . $product->slug,
                'schema_markup'    => $this->productSchema($product),
            ]);
        }
    }

    private function curatedSeo(): array
    {
        return [
            'iPhone 15 Pro Max' => [
                'meta_title'       => 'iPhone 15 Pro Max – Titanium, A17 Pro, 5× Zoom',
                'meta_description' => 'Buy iPhone 15 Pro Max with A17 Pro chip, aerospace-grade titanium, 48MP camera, 5× optical zoom, and USB 3. Choose from Natural, Black, White, or Desert Titanium.',
                'meta_keywords'    => 'iPhone 15 Pro Max, Apple smartphone, A17 Pro chip, titanium iPhone, 5x zoom, USB-C iPhone',
                'og_title'         => 'iPhone 15 Pro Max – The Ultimate iPhone',
                'og_description'   => 'Titanium design meets the A17 Pro chip. Shop iPhone 15 Pro Max in four titanium finishes.',
                'og_image'         => null,
                'canonical_url'    => '/products/iphone-15-pro-max',
                'schema_markup'    => [
                    '@context' => 'https://schema.org',
                    '@type'    => 'Product',
                    'name'     => 'iPhone 15 Pro Max',
                    'brand'    => ['@type' => 'Brand', 'name' => 'Apple'],
                    'offers'   => ['@type' => 'Offer', 'priceCurrency' => 'USD', 'price' => '1199.00', 'availability' => 'https://schema.org/InStock'],
                ],
            ],
            'Samsung Galaxy S24 Ultra' => [
                'meta_title'       => 'Samsung Galaxy S24 Ultra – 200MP AI Camera & S Pen',
                'meta_description' => 'Shop Samsung Galaxy S24 Ultra with Galaxy AI, 200MP camera, built-in S Pen, and titanium frame. Available in 256GB, 512GB, and 1TB configurations.',
                'meta_keywords'    => 'Samsung Galaxy S24 Ultra, Galaxy AI, 200MP camera, S Pen, titanium smartphone, Android flagship',
                'og_title'         => 'Samsung Galaxy S24 Ultra – AI-Powered Flagship',
                'og_description'   => 'Galaxy AI, 200MP quad-camera, and integrated S Pen in a titanium shell. The most capable Galaxy yet.',
                'og_image'         => null,
                'canonical_url'    => '/products/samsung-galaxy-s24-ultra',
                'schema_markup'    => [
                    '@context' => 'https://schema.org',
                    '@type'    => 'Product',
                    'name'     => 'Samsung Galaxy S24 Ultra',
                    'brand'    => ['@type' => 'Brand', 'name' => 'Samsung'],
                    'offers'   => ['@type' => 'Offer', 'priceCurrency' => 'USD', 'price' => '1299.00', 'availability' => 'https://schema.org/InStock'],
                ],
            ],
            'iPad Pro 13" M4' => [
                'meta_title'       => 'iPad Pro 13" M4 – Ultra Retina XDR OLED, Thinnest iPad',
                'meta_description' => 'Buy iPad Pro 13" with M4 chip and Ultra Retina XDR OLED display. Apple\'s thinnest product ever at 5.1mm. Supports Apple Pencil Pro and Magic Keyboard.',
                'meta_keywords'    => 'iPad Pro M4, iPad Pro 13 inch, M4 chip tablet, OLED iPad, Apple Pencil Pro, thinnest iPad',
                'og_title'         => 'iPad Pro 13" M4 – The Most Advanced iPad Ever',
                'og_description'   => 'Ultra Retina XDR OLED, M4 performance, and Apple Pencil Pro support in just 5.1mm.',
                'og_image'         => null,
                'canonical_url'    => '/products/ipad-pro-13-m4',
                'schema_markup'    => [
                    '@context' => 'https://schema.org',
                    '@type'    => 'Product',
                    'name'     => 'iPad Pro 13" M4',
                    'brand'    => ['@type' => 'Brand', 'name' => 'Apple'],
                    'offers'   => ['@type' => 'Offer', 'priceCurrency' => 'USD', 'price' => '1299.00', 'availability' => 'https://schema.org/InStock'],
                ],
            ],
            'Anker 100W 4-Port GaN Charger' => [
                'meta_title'       => 'Anker 100W 4-Port GaN Charger – Charge 4 Devices at Once',
                'meta_description' => 'Replace four chargers with one. Anker 100W GaN desktop charger with two USB-C PD ports and two USB-A ports. ActiveShield 2.0 temperature protection.',
                'meta_keywords'    => 'Anker GaN charger, 100W charger, 4 port charger, USB-C PD charger, multi device charger',
                'og_title'         => 'Anker 100W 4-Port GaN Charger',
                'og_description'   => 'One charger. Four devices. 100W total GaN power with ActiveShield temperature monitoring.',
                'og_image'         => null,
                'canonical_url'    => '/products/anker-100w-4-port-gan-charger',
                'schema_markup'    => [
                    '@context' => 'https://schema.org',
                    '@type'    => 'Product',
                    'name'     => 'Anker 100W 4-Port GaN Charger',
                    'brand'    => ['@type' => 'Brand', 'name' => 'Anker'],
                    'offers'   => ['@type' => 'Offer', 'priceCurrency' => 'USD', 'price' => '69.99', 'availability' => 'https://schema.org/InStock'],
                ],
            ],
            '20000mAh Power Bank PD 65W' => [
                'meta_title'       => '20000mAh Power Bank 65W – Charge Laptops & Phones',
                'meta_description' => 'Powerful 20000mAh portable charger with 65W USB-C PD output. Charges iPhone 15 over 4 times and a 13" MacBook from 0-50% in 1 hour. Compact and airline-safe.',
                'meta_keywords'    => '20000mah power bank, 65W power bank, laptop power bank, USB-C PD power bank, portable charger',
                'og_title'         => '20000mAh 65W Power Bank – Power for Everything',
                'og_description'   => '65W PD output, 20,000mAh capacity. Charges your laptop, phone, and tablet all day long.',
                'og_image'         => null,
                'canonical_url'    => '/products/20000mah-power-bank-pd-65w',
                'schema_markup'    => [
                    '@context' => 'https://schema.org',
                    '@type'    => 'Product',
                    'name'     => '20000mAh Power Bank PD 65W',
                    'offers'   => ['@type' => 'Offer', 'priceCurrency' => 'USD', 'price' => '59.99', 'availability' => 'https://schema.org/InStock'],
                ],
            ],
            'Belkin BoostCharge Pro 3-in-1' => [
                'meta_title'       => 'Belkin BoostCharge Pro 3-in-1 MagSafe Charging Pad',
                'meta_description' => 'MagSafe-certified 15W wireless charging for iPhone, AirPods, and Apple Watch simultaneously. Clean desk setup with a single USB-C cable. By Belkin.',
                'meta_keywords'    => 'Belkin 3-in-1 charger, MagSafe charging pad, iPhone Apple Watch AirPods charger, wireless charging station',
                'og_title'         => 'Belkin BoostCharge Pro 3-in-1 Wireless Charger',
                'og_description'   => 'Apple-certified MagSafe charger for iPhone, AirPods, and Apple Watch. One pad, no cable mess.',
                'og_image'         => null,
                'canonical_url'    => '/products/belkin-boostcharge-pro-3-in-1',
                'schema_markup'    => [
                    '@context' => 'https://schema.org',
                    '@type'    => 'Product',
                    'name'     => 'Belkin BoostCharge Pro 3-in-1',
                    'brand'    => ['@type' => 'Brand', 'name' => 'Belkin'],
                    'offers'   => ['@type' => 'Offer', 'priceCurrency' => 'USD', 'price' => '79.99', 'availability' => 'https://schema.org/InStock'],
                ],
            ],
        ];
    }

    private function productSchema(Product $product): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type'    => 'Product',
            'name'     => $product->name,
            'sku'      => $product->sku,
            'brand'    => $product->brand
                ? ['@type' => 'Brand', 'name' => $product->brand->name]
                : null,
            'offers'   => [
                '@type'         => 'Offer',
                'priceCurrency' => 'USD',
                'price'         => (string) $product->selling_price,
                'availability'  => $product->stock_quantity > 0
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
            ],
        ];
    }
}
