<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontProductTest extends TestCase
{
    use RefreshDatabase;

    private Category $cat;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cat = Category::create(['name' => 'Phones']);
    }

    private function makeProduct(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'name'          => 'Test Phone',
            'sku'           => 'TST-' . uniqid(),
            'category_id'   => $this->cat->id,
            'cost_price'    => 100,
            'selling_price' => 199,
            'stock_quantity'=> 10,
            'is_active'     => true,
            'status'        => 'active',
        ], $overrides));
    }

    public function test_active_products_appear_in_storefront(): void
    {
        $this->makeProduct(['name' => 'Visible Phone']);

        $response = $this->getJson('/api/storefront/products');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(1, count($response->json('data')));
        $names = collect($response->json('data'))->pluck('name');
        $this->assertContains('Visible Phone', $names);
    }

    public function test_draft_products_are_hidden_from_storefront(): void
    {
        $this->makeProduct(['name' => 'Draft Phone', 'status' => 'draft']);

        $response = $this->getJson('/api/storefront/products');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name');
        $this->assertNotContains('Draft Phone', $names);
    }

    public function test_inactive_products_are_hidden_from_storefront(): void
    {
        $this->makeProduct(['name' => 'Inactive Phone', 'is_active' => false]);

        $response = $this->getJson('/api/storefront/products');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name');
        $this->assertNotContains('Inactive Phone', $names);
    }

    public function test_new_product_created_via_repo_defaults_to_active_status(): void
    {
        $product = Product::create([
            'name'          => 'Repo Product',
            'sku'           => 'REPO-001',
            'category_id'   => $this->cat->id,
            'cost_price'    => 50,
            'selling_price' => 99,
            'stock_quantity'=> 5,
        ]);

        // Column default is now 'active' — no explicit status needed
        $this->assertEquals('active', $product->fresh()->status->value);
    }

    public function test_storefront_search_works(): void
    {
        $this->makeProduct(['name' => 'Galaxy S24']);
        $this->makeProduct(['name' => 'iPhone 15']);

        $response = $this->getJson('/api/storefront/products?search=Galaxy');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name');
        $this->assertContains('Galaxy S24', $names);
        $this->assertNotContains('iPhone 15', $names);
    }

    public function test_storefront_category_filter_works(): void
    {
        $other = Category::create(['name' => 'Tablets']);
        $this->makeProduct(['name' => 'Phone in Cat',   'category_id' => $this->cat->id]);
        $this->makeProduct(['name' => 'Tablet in Other', 'category_id' => $other->id]);

        $response = $this->getJson("/api/storefront/products?category_id={$this->cat->id}");

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name');
        $this->assertContains('Phone in Cat', $names);
        $this->assertNotContains('Tablet in Other', $names);
    }
}
