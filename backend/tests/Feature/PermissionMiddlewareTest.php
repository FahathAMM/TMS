<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PermissionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view products', 'create products', 'edit products', 'delete products',
            'view categories', 'create categories', 'edit categories', 'delete categories',
            'view brands', 'create brands', 'edit brands', 'delete brands',
            'view customers', 'create customers', 'edit customers', 'delete customers',
            'view sales', 'create sales', 'cancel sales',
            'view inventory', 'adjust inventory',
            'view dashboard',
            'view users', 'create users', 'edit users', 'delete users',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->syncPermissions(Permission::where('guard_name', 'web')->get());

        $staffRole = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        $staffRole->syncPermissions([
            'view products', 'create products', 'edit products',
            'view categories',
            'view brands',
            'view customers', 'create customers', 'edit customers',
            'view sales', 'create sales',
            'view inventory', 'adjust inventory',
            'view dashboard',
        ]);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->syncRoles([Role::findByName('admin', 'web')]);

        $this->staff = User::factory()->create(['is_active' => true]);
        $this->staff->syncRoles([Role::findByName('staff', 'web')]);
    }

    // ── Admin can do everything ──────────────────────────────────────────────

    public function test_admin_can_list_products(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/products')
            ->assertOk();
    }

    public function test_admin_can_create_category(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/categories', ['name' => 'Test Category'])
            ->assertCreated();
    }

    public function test_admin_can_create_brand(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/brands', ['name' => 'Test Brand'])
            ->assertCreated();
    }

    public function test_admin_can_update_brand(): void
    {
        // create a brand first as admin
        $brand = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/brands', ['name' => 'Brand To Edit'])
            ->assertCreated()
            ->json('data');

        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/brands/{$brand['id']}", ['name' => 'Brand Renamed'])
            ->assertOk();
    }

    public function test_admin_can_delete_category(): void
    {
        $cat = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/categories', ['name' => 'Delete Me'])
            ->assertCreated()
            ->json('data');

        $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/categories/{$cat['id']}")
            ->assertOk();
    }

    // ── Staff can do permitted actions ───────────────────────────────────────

    public function test_staff_can_create_product(): void
    {
        $cat = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/categories', ['name' => 'Staff Cat'])
            ->json('data');

        $this->actingAs($this->staff, 'sanctum')
            ->postJson('/api/products', [
                'name'          => 'Staff Product',
                'sku'           => 'ST-001',
                'selling_price' => 10,
                'cost_price'    => 5,
                'stock_quantity'=> 10,
                'category_id'   => $cat['id'],
            ])
            ->assertCreated();
    }

    public function test_staff_cannot_delete_brand(): void
    {
        $brand = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/brands', ['name' => 'Staff Cannot Delete'])
            ->json('data');

        $this->actingAs($this->staff, 'sanctum')
            ->deleteJson("/api/brands/{$brand['id']}")
            ->assertForbidden();
    }

    public function test_staff_cannot_access_users_list(): void
    {
        $this->actingAs($this->staff, 'sanctum')
            ->getJson('/api/users')
            ->assertForbidden();
    }

    // ── Unauthenticated is rejected ──────────────────────────────────────────

    public function test_unauthenticated_cannot_create_product(): void
    {
        $this->postJson('/api/products', ['name' => 'Hack'])
            ->assertUnauthorized();
    }
}
