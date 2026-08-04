<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Brand;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProductImageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Category $category;
    private Brand $brand;

    protected function setUp(): void
    {
        parent::setUp();

        // Bootstrap Spatie permissions for this test
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Permission::firstOrCreate(['name' => 'create products']);
        Permission::firstOrCreate(['name' => 'edit products']);
        Permission::firstOrCreate(['name' => 'delete products']);

        $this->admin = User::factory()->admin()->create();
        $this->admin->givePermissionTo(['create products', 'edit products', 'delete products']);

        $this->category = Category::create(['name' => 'Test Category']);
        $this->brand    = Brand::create(['name' => 'Test Brand']);
    }

    private function productPayload(array $overrides = []): array
    {
        return array_merge([
            'name'                => 'Test Phone',
            'category_id'         => $this->category->id,
            'cost_price'          => '100.00',
            'selling_price'       => '149.99',
            'stock_quantity'      => '10',
        ], $overrides);
    }

    private function actingAsAdmin()
    {
        $token = $this->admin->createToken('test')->plainTextToken;
        return $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept'        => 'application/json',
        ]);
    }

    // ─── Create ───────────────────────────────────────────────────────────────

    public function test_create_product_without_image(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson('/api/products', $this->productPayload());

        $response->assertCreated()
            ->assertJsonPath('data.image', null);

        $this->assertDatabaseHas('products', ['name' => 'Test Phone']);
    }

    public function test_create_product_with_image(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('product.jpg', 400, 400);

        $response = $this->actingAsAdmin()
            ->post('/api/products', array_merge($this->productPayload(), ['image' => $file]));

        $response->assertCreated();

        $product = Product::where('name', 'Test Phone')->first();
        $this->assertNotNull($product->image, 'Image path should be stored in DB');
        Storage::disk('public')->assertExists($product->image);
    }

    public function test_create_product_rejects_non_image_file(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

        $response = $this->actingAsAdmin()
            ->post('/api/products', array_merge($this->productPayload(), ['image' => $file]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    public function test_create_product_rejects_image_over_5mb(): void
    {
        Storage::fake('public');

        // 5121 KB = just over 5 MB limit (max:5120 in validation)
        $file = UploadedFile::fake()->image('big.jpg')->size(5121);

        $response = $this->actingAsAdmin()
            ->post('/api/products', array_merge($this->productPayload(), ['image' => $file]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    // ─── Update ───────────────────────────────────────────────────────────────

    public function test_update_product_with_new_image_replaces_old(): void
    {
        Storage::fake('public');

        // Create with an initial image
        $original = UploadedFile::fake()->image('original.jpg');
        $this->actingAsAdmin()
            ->post('/api/products', array_merge($this->productPayload(), ['image' => $original]));

        $product = Product::where('name', 'Test Phone')->first();
        $oldPath = $product->image;
        Storage::disk('public')->assertExists($oldPath);

        // Update with a new image
        $newFile = UploadedFile::fake()->image('new.jpg', 300, 300);
        $response = $this->actingAsAdmin()
            ->put("/api/products/{$product->id}", ['name' => 'Test Phone Updated', 'image' => $newFile]);

        $response->assertOk();

        $product->refresh();
        $this->assertNotNull($product->image);
        $this->assertNotEquals($oldPath, $product->image, 'Image path should change after update');
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($product->image);
    }

    public function test_update_product_without_image_keeps_existing(): void
    {
        Storage::fake('public');

        $original = UploadedFile::fake()->image('keep.jpg');
        $this->actingAsAdmin()
            ->post('/api/products', array_merge($this->productPayload(), ['image' => $original]));

        $product  = Product::where('name', 'Test Phone')->first();
        $oldPath  = $product->image;

        $response = $this->actingAsAdmin()
            ->putJson("/api/products/{$product->id}", ['name' => 'Test Phone Renamed']);

        $response->assertOk();

        $product->refresh();
        $this->assertEquals($oldPath, $product->image, 'Existing image should be preserved');
        Storage::disk('public')->assertExists($oldPath);
    }

    public function test_image_url_returned_in_response(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('check.jpg');

        $response = $this->actingAsAdmin()
            ->post('/api/products', array_merge($this->productPayload(), ['image' => $file]));

        $response->assertCreated()
            ->assertJsonPath('data.image', fn($v) => str_contains($v, '/storage/'));
    }

    public function test_delete_product_removes_image_from_storage(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('delete-me.jpg');
        $this->actingAsAdmin()
            ->post('/api/products', array_merge($this->productPayload(), ['image' => $file]));

        $product = Product::where('name', 'Test Phone')->first();
        $path    = $product->image;
        Storage::disk('public')->assertExists($path);

        $this->actingAsAdmin()->deleteJson("/api/products/{$product->id}");

        Storage::disk('public')->assertMissing($path);
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }
}
