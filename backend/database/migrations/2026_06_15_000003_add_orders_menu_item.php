<?php

use App\Models\Menu;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        // Find the "Customers & Sales" section
        $section = Menu::where('slug', 'section_sales')->first();
        if (!$section) return;

        // Shift offers sort_order from 3 → 4 to make room
        Menu::where('slug', 'offers')->update(['sort_order' => 4]);
        Menu::where('slug', 'contact_messages')->update(['sort_order' => 10]);

        // Insert the Orders menu item
        $existing = Menu::where('slug', 'orders')->first();
        if (!$existing) {
            Menu::create([
                'slug'       => 'orders',
                'name'       => 'Orders',
                'route_name' => '/orders',
                'icon'       => 'ShoppingBag',
                'parent_id'  => $section->id,
                'sort_order' => 3,
                'is_active'  => true,
            ]);
            // Model boot() auto-creates view/create/edit/delete permissions
        } else {
            // Menu exists but may be missing its permission
            Permission::firstOrCreate(['name' => 'view orders', 'guard_name' => 'web']);
        }

        $admin = \Spatie\Permission\Models\Role::where('name', 'admin')->where('guard_name', 'web')->first();
        if ($admin) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            $admin->syncPermissions(Permission::where('guard_name', 'web')->get());
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    public function down(): void
    {
        Menu::where('slug', 'orders')->delete();
        Menu::where('slug', 'offers')->update(['sort_order' => 3]);
    }
};
