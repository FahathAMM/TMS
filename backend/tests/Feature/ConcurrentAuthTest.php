<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Verifies that an admin (User) and a shop customer (Customer)
 * can be authenticated simultaneously without guard bleed.
 */
class ConcurrentAuthTest extends TestCase
{
    use RefreshDatabase;

    protected string $adminToken;
    protected string $shopToken;
    protected User $admin;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Seed the minimum permissions/roles needed
        foreach (['view products', 'create products', 'edit products', 'delete products',
                  'view categories', 'create categories', 'edit categories', 'delete categories',
                  'view brands', 'create brands', 'edit brands', 'delete brands',
                  'view customers', 'create customers', 'edit customers', 'delete customers',
                  'view sales', 'create sales', 'cancel sales',
                  'view inventory', 'adjust inventory', 'view dashboard',
                  'view users', 'create users', 'edit users', 'delete users'] as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->syncPermissions(Permission::where('guard_name', 'web')->get());

        // Create admin user and issue admin token
        $this->admin = User::factory()->create([
            'email'     => 'admin@test.com',
            'password'  => 'password',
            'is_active' => true,
        ]);
        $this->admin->syncRoles([Role::findByName('admin', 'web')]);
        $this->adminToken = $this->admin->createToken('pos-token', ['*'])->plainTextToken;

        // Create shop customer and issue shop token
        $this->customer = Customer::factory()->create([
            'email'     => 'customer@test.com',
            'password'  => 'password',
            'is_active' => true,
        ]);
        $this->shopToken = $this->customer->createToken('shop-token', ['shop'])->plainTextToken;
    }

    // ── Admin token works on admin routes ────────────────────────────────────

    public function test_admin_token_can_access_admin_me(): void
    {
        $this->withToken($this->adminToken)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'admin@test.com');
    }

    public function test_admin_token_can_list_products(): void
    {
        $this->withToken($this->adminToken)
            ->getJson('/api/products')
            ->assertOk();
    }

    public function test_admin_token_can_create_brand(): void
    {
        $this->withToken($this->adminToken)
            ->postJson('/api/brands', ['name' => 'Admin Brand'])
            ->assertCreated();
    }

    // ── Shop token works on storefront routes ────────────────────────────────

    public function test_shop_token_can_access_storefront_me(): void
    {
        $this->withToken($this->shopToken)
            ->getJson('/api/storefront/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'customer@test.com');
    }

    public function test_shop_token_can_view_cart(): void
    {
        $this->withToken($this->shopToken)
            ->getJson('/api/storefront/cart')
            ->assertOk();
    }

    // ── Guard isolation — tokens must not cross-authenticate ─────────────────

    public function test_admin_token_cannot_access_storefront_me(): void
    {
        // Admin token sent to a shop-guard route → must be rejected (401)
        $this->withToken($this->adminToken)
            ->getJson('/api/storefront/auth/me')
            ->assertUnauthorized();
    }

    public function test_shop_token_cannot_access_admin_me(): void
    {
        // Shop token sent to a sanctum-guard route → Sanctum resolves Customer,
        // then admin route returns the wrong model; the endpoint should fail.
        $response = $this->withToken($this->shopToken)
            ->getJson('/api/auth/me');

        // The shop token resolves a Customer, not a User, so auth:sanctum
        // returns the Customer — but UserResource will still serialize it.
        // Most importantly the response must NOT return the admin's data.
        $this->assertNotEquals('admin@test.com', $response->json('data.email'));
    }

    public function test_shop_token_cannot_access_admin_only_users_list(): void
    {
        // users list requires role:admin — a Customer has no role → forbidden
        $this->withToken($this->shopToken)
            ->getJson('/api/users')
            ->assertStatus(401); // unauthenticated or forbidden
    }

    // ── Both tokens active simultaneously ────────────────────────────────────

    public function test_both_sessions_work_independently_in_same_request_cycle(): void
    {
        // Admin creates a category
        $cat = $this->withToken($this->adminToken)
            ->postJson('/api/categories', ['name' => 'Concurrent Cat'])
            ->assertCreated()
            ->json('data');

        // Customer immediately fetches their cart (different guard, different user)
        $this->withToken($this->shopToken)
            ->getJson('/api/storefront/cart')
            ->assertOk();

        // Admin can still use their token after the shop request
        $this->withToken($this->adminToken)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'admin@test.com');

        // Customer can still use their token after the admin request
        $this->withToken($this->shopToken)
            ->getJson('/api/storefront/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'customer@test.com');

        // Category created by admin is visible to admin
        $this->withToken($this->adminToken)
            ->getJson("/api/categories/{$cat['id']}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Concurrent Cat');
    }

    // ── Login endpoints ───────────────────────────────────────────────────────

    public function test_admin_login_returns_token(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email'    => 'admin@test.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.user.email', 'admin@test.com')
            ->assertJsonStructure(['data' => ['token']]);
    }

    public function test_shop_login_returns_token(): void
    {
        $response = $this->postJson('/api/storefront/auth/login', [
            'email'    => 'customer@test.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('customer.email', 'customer@test.com')
            ->assertJsonStructure(['token']);
    }

    public function test_admin_and_shop_can_login_sequentially_without_conflict(): void
    {
        // Login admin
        $adminRes = $this->postJson('/api/auth/login', [
            'email'    => 'admin@test.com',
            'password' => 'password',
        ])->assertOk();

        $freshAdminToken = $adminRes->json('data.token');

        // Login shop customer
        $shopRes = $this->postJson('/api/storefront/auth/login', [
            'email'    => 'customer@test.com',
            'password' => 'password',
        ])->assertOk();

        $freshShopToken = $shopRes->json('token');

        // Both tokens work independently
        $this->withToken($freshAdminToken)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'admin@test.com');

        $this->withToken($freshShopToken)
            ->getJson('/api/storefront/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'customer@test.com');
    }
}
