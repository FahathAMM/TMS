<?php

namespace Database\Seeders;

use App\Models\Administration\Menu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class MenuSeeder extends Seeder
{
    /**
     * Every module the sidebar can show, as data instead of imperative calls.
     * 'key' is just this node's own local segment — the stored slug is built
     * by walking the tree and joining each ancestor's key with '-', e.g.
     * tailoring -> orders becomes the slug "tailoring-orders".
     * A module with no 'children' is a root stand-alone item (e.g. Dashboard).
     * A module with 'children' is rendered as a section header whose children
     * become the actual menu items underneath it.
     */
    private array $modules = [
        [
            'key' => 'dashboard',
            'name' => 'Dashboard',
            'route_name' => '/dashboard',
            'icon' => 'LayoutDashboard',
            'sort_order' => 1,
        ],

        [
            'key' => 'customers',
            'name' => 'Customers',
            'icon' => 'UserCircle',
            'sort_order' => 5,
            'children' => [
                [
                    'key' => 'customers',
                    'name' => 'Customers',
                    'route_name' => '/customers',
                    'icon' => 'UserCircle',
                    'sort_order' => 1
                ],
            ],
        ],

        [
            'key' => 'tailoring',
            'name' => 'Tailoring',
            'icon' => 'Shirt',
            'sort_order' => 6,
            'children' => [
                [
                    'key' => 'orders',
                    'name' => 'Orders',
                    'route_name' => '/orders',
                    'icon' => 'ShoppingCart',
                    'sort_order' => 1
                ],
                [
                    'key' => 'production',
                    'name' => 'Production',
                    'route_name' => '/production',
                    'icon' => 'ClipboardList',
                    'sort_order' => 2
                ],
                [
                    'key' => 'alteration',
                    'name' => 'Alteration',
                    'route_name' => '/alteration',
                    'icon' => 'Scissors',
                    'sort_order' => 3
                ],
                [
                    'key' => 'tailors',
                    'name' => 'Tailors',
                    'route_name' => '/tailors',
                    'icon' => 'Users',
                    'sort_order' => 4
                ],
                [
                    'key' => 'measurement_types',
                    'name' => 'Measurement Types',
                    'route_name' => '/measurement-types',
                    'icon' => 'FileText',
                    'sort_order' => 5
                ],
                [
                    'key' => 'garment_prices',
                    'name' => 'Price List',
                    'route_name' => '/garment-prices',
                    'icon' => 'Tag',
                    'sort_order' => 6
                ],
                [
                    'key' => 'alteration_types',
                    'name' => 'Alteration Types',
                    'route_name' => '/alteration-types',
                    'icon' => 'Scissors',
                    'sort_order' => 7
                ],
            ],
        ],

        [
            'key' => 'inventory',
            'name' => 'Inventory',
            'icon' => 'Warehouse',
            'sort_order' => 8,
            'children' => [
                [
                    'key' => 'products',
                    'name' => 'Fabrics & Trims',
                    'route_name' => '/products',
                    'icon' => 'Package',
                    'sort_order' => 1
                ],
                [
                    'key' => 'suppliers',
                    'name' => 'Suppliers',
                    'route_name' => '/suppliers',
                    'icon' => 'Truck',
                    'sort_order' => 2
                ],
                [
                    'key' => 'purchases',
                    'name' => 'Purchase Orders',
                    'route_name' => '/purchases',
                    'icon' => 'Warehouse',
                    'sort_order' => 3
                ],
                [
                    'key' => 'stock_movements',
                    'name' => 'Stock Movements',
                    'route_name' => '/stock-movements',
                    'icon' => 'History',
                    'sort_order' => 4
                ],
            ],
        ],

        // Admin-only, no staff permission sync.
        [
            'key' => 'accounting',
            'name' => 'Accounting',
            'icon' => 'DollarSign',
            'sort_order' => 8,
            'children' => [
                [
                    'key' => 'accounts',
                    'name' => 'Chart of Accounts',
                    'route_name' => '/accounting/accounts',
                    'icon' => 'Database',
                    'sort_order' => 1
                ],
                [
                    'key' => 'journal',
                    'name' => 'Journal',
                    'route_name' => '/accounting/journal',
                    'icon' => 'BookOpen',
                    'sort_order' => 2
                ],
                [
                    'key' => 'reports',
                    'name' => 'Reports',
                    'route_name' => '/accounting/reports',
                    'icon' => 'BarChart2',
                    'sort_order' => 3
                ],
                [
                    'key' => 'expenses',
                    'name' => 'Expenses',
                    'route_name' => '/expenses',
                    'icon' => 'Receipt',
                    'sort_order' => 4
                ],
            ],
        ],

        // Business/operational reports, not GL.
        [
            'key' => 'reports',
            'name' => 'Reports',
            'icon' => 'PieChart',
            'sort_order' => 9,
            'children' => [
                [
                    'key' => 'reports',
                    'name' => 'Reports',
                    'route_name' => '/reports',
                    'icon' => 'BarChart2',
                    'sort_order' => 1
                ],
            ],
        ],

        [
            'key' => 'admin',
            'name' => 'Administration',
            'icon' => 'Lock',
            'sort_order' => 10,
            'children' => [
                [
                    'key' => 'users',
                    'name' => 'Users',
                    'route_name' => '/users',
                    'icon' => 'Users',
                    'sort_order' => 1
                ],
                [
                    'key' => 'roles',
                    'name' => 'Roles',
                    'route_name' => '/roles',
                    'icon' => 'ShieldCheck',
                    'sort_order' => 2
                ],
                [
                    'key' => 'menus',
                    'name' => 'Menus',
                    'route_name' => '/menus',
                    'icon' => 'LayoutList',
                    'sort_order' => 3
                ],
                [
                    'key' => 'settings',
                    'name' => 'Settings',
                    'route_name' => '/settings',
                    'icon' => 'Settings2',
                    'sort_order' => 4
                ],
                [
                    'key' => 'audit_logs',
                    'name' => 'Audit Logs',
                    'route_name' => '/audit-logs',
                    'icon' => 'Activity',
                    'sort_order' => 5
                ],
            ],
        ],
    ];

    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('model_has_permissions')->truncate();
        DB::table('role_has_permissions')->truncate();
        Permission::truncate();
        Menu::truncate();
        Schema::enableForeignKeyConstraints();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // super-admin: cannot be deleted (RoleController::destroy) and
        // bypasses every permission/role check (AppServiceProvider::boot,
        // User::hasRole) — so it's never synced permissions, it just
        // doesn't need any.
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        DB::transaction(function () {
            foreach ($this->modules as $module) {
                $this->seedModule($module, null, null);
            }
        });

        $this->generatePermissions();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $admin = Role::findByName('admin', 'web');
        $admin->syncPermissions(Permission::where('guard_name', 'web')->get());

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Recursively upserts a module (and its children, if any), building the
     * stored slug as parent-child-child... by chaining each ancestor's key.
     */
    private function seedModule(array $module, ?string $parentSlug, ?int $parentId): void
    {
        $slug = $parentSlug ? "{$parentSlug}-{$module['key']}" : $module['key'];

        if (isset($module['children'])) {
            $parent = $this->section($slug, $module['name'], $module['sort_order'], $module['icon'] ?? null, $parentId);
            Log::info("Seeding menu item: {$slug} ({$module['name']})");

            foreach ($module['children'] as $child) {
                $this->seedModule($child, $slug, $parent->id);
            }
        } else {
            Log::info("Seeding menu item: {$slug} ({$module['name']})");
            $this->upsert($slug, [
                'name'       => $module['name'],
                'route_name' => $module['route_name'],
                'icon'       => $module['icon'] ?? null,
                'sort_order' => $module['sort_order'],
                'parent_id'  => $parentId,
            ]);
        }
    }

    private function upsert(string $slug, array $attrs): Menu
    {
        $menu = Menu::firstOrNew(['slug' => $slug]);
        $menu->slug       = $slug;
        $menu->name       = $attrs['name'];
        $menu->route_name = $attrs['route_name'];
        $menu->icon       = $attrs['icon'];
        $menu->sort_order = $attrs['sort_order'];
        $menu->parent_id  = $attrs['parent_id'];
        $menu->is_active  = true;
        $menu->save();
        return $menu;
    }

    /**
     * For every navigable menu (has a route), create its 4 CRUD permissions
     * named "{slug}-view", "{slug}-create", "{slug}-edit", "{slug}-delete".
     */
    private function generatePermissions(): void
    {
        $slugs = Menu::whereNotNull('route_name')->pluck('slug');

        foreach ($slugs as $slug) {
            foreach (['view', 'create', 'edit', 'delete'] as $action) {
                Permission::firstOrCreate([
                    'name'       => "{$slug}-{$action}",
                    'guard_name' => 'web',
                ]);
            }
        }
    }

    private function section(string $slug, string $name, int $sortOrder, ?string $icon = null, ?int $parentId = null): Menu
    {
        $menu = Menu::firstOrNew(['slug' => $slug]);
        $menu->fill([
            'slug'       => $slug,
            'name'       => $name,
            'route_name' => null,
            'icon'       => $icon,
            'parent_id'  => $parentId,
            'sort_order' => $sortOrder,
            'is_active'  => true,
        ]);
        $menu->save();
        return $menu;
    }
}
