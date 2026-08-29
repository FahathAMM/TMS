<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        DB::transaction(function () {

            // ── 1. Root stand-alone items ────────────────────────────────────────
            $this->upsert('dashboard', [
                'name' => 'Dashboard', 'route_name' => '/dashboard',
                'icon' => 'LayoutDashboard', 'sort_order' => 1, 'parent_id' => null,
            ]);

            // ── 2. Section: Customers ────────────────────────────────────────────
            $sCustomers = $this->section('section_customers', 'Customers', 5, 'UserCircle');

            $this->upsert('customers', [
                'name' => 'Customers', 'route_name' => '/customers',
                'icon' => 'UserCircle', 'sort_order' => 1, 'parent_id' => $sCustomers->id,
            ]);

            // ── 2b. Section: Tailoring ────────────────────────────────────────────
            $sTailoring = $this->section('section_tailoring', 'Tailoring', 6, 'Shirt');

            $this->upsert('tailoring_orders', [
                'name' => 'Orders', 'route_name' => '/orders',
                'icon' => 'ShoppingCart', 'sort_order' => 1, 'parent_id' => $sTailoring->id,
            ]);
            $this->upsert('production', [
                'name' => 'Production', 'route_name' => '/production',
                'icon' => 'ClipboardList', 'sort_order' => 2, 'parent_id' => $sTailoring->id,
            ]);
            $this->upsert('alteration', [
                'name' => 'Alteration', 'route_name' => '/alteration',
                'icon' => 'Scissors', 'sort_order' => 3, 'parent_id' => $sTailoring->id,
            ]);
            $this->upsert('tailors', [
                'name' => 'Tailors', 'route_name' => '/tailors',
                'icon' => 'Users', 'sort_order' => 4, 'parent_id' => $sTailoring->id,
            ]);
            $this->upsert('measurement_types', [
                'name' => 'Measurement Types', 'route_name' => '/measurement-types',
                'icon' => 'FileText', 'sort_order' => 5, 'parent_id' => $sTailoring->id,
            ]);
            $this->upsert('garment_prices', [
                'name' => 'Price List', 'route_name' => '/garment-prices',
                'icon' => 'Tag', 'sort_order' => 6, 'parent_id' => $sTailoring->id,
            ]);
            $this->upsert('alteration_types', [
                'name' => 'Alteration Types', 'route_name' => '/alteration-types',
                'icon' => 'Scissors', 'sort_order' => 7, 'parent_id' => $sTailoring->id,
            ]);

            // ── 2c. Section: Inventory ───────────────────────────────────────────
            $sInventory = $this->section('section_inventory', 'Inventory', 8, 'Warehouse');

            $this->upsert('products', [
                'name' => 'Fabrics & Trims', 'route_name' => '/products',
                'icon' => 'Package', 'sort_order' => 1, 'parent_id' => $sInventory->id,
            ]);
            $this->upsert('suppliers', [
                'name' => 'Suppliers', 'route_name' => '/suppliers',
                'icon' => 'Truck', 'sort_order' => 2, 'parent_id' => $sInventory->id,
            ]);
            $this->upsert('purchases', [
                'name' => 'Purchase Orders', 'route_name' => '/purchases',
                'icon' => 'Warehouse', 'sort_order' => 3, 'parent_id' => $sInventory->id,
            ]);
            $this->upsert('stock_movements', [
                'name' => 'Stock Movements', 'route_name' => '/stock-movements',
                'icon' => 'History', 'sort_order' => 4, 'parent_id' => $sInventory->id,
            ]);

            // ── 2d. Section: Accounting (admin-only, no staff permission sync) ────
            $sAccounting = $this->section('section_accounting', 'Accounting', 8, 'DollarSign');

            $this->upsert('accounting_accounts', [
                'name' => 'Chart of Accounts', 'route_name' => '/accounting/accounts',
                'icon' => 'Database', 'sort_order' => 1, 'parent_id' => $sAccounting->id,
            ]);
            $this->upsert('accounting_journal', [
                'name' => 'Journal', 'route_name' => '/accounting/journal',
                'icon' => 'BookOpen', 'sort_order' => 2, 'parent_id' => $sAccounting->id,
            ]);
            $this->upsert('accounting_reports', [
                'name' => 'Reports', 'route_name' => '/accounting/reports',
                'icon' => 'BarChart2', 'sort_order' => 3, 'parent_id' => $sAccounting->id,
            ]);
            $this->upsert('expenses', [
                'name' => 'Expenses', 'route_name' => '/expenses',
                'icon' => 'Receipt', 'sort_order' => 4, 'parent_id' => $sAccounting->id,
            ]);

            // ── 2e. Section: Reports (business/operational, not GL) ────────────────
            $sReports = $this->section('section_reports', 'Reports', 9, 'PieChart');

            $this->upsert('reports', [
                'name' => 'Reports', 'route_name' => '/reports',
                'icon' => 'BarChart2', 'sort_order' => 1, 'parent_id' => $sReports->id,
            ]);

            // ── 3. Section: Administration ───────────────────────────────────────
            $sAdmin = $this->section('section_admin', 'Administration', 10, 'Lock');

            $this->upsert('users', [
                'name' => 'Users', 'route_name' => '/users',
                'icon' => 'Users', 'sort_order' => 1, 'parent_id' => $sAdmin->id,
            ]);
            $this->upsert('roles', [
                'name' => 'Roles', 'route_name' => '/roles',
                'icon' => 'ShieldCheck', 'sort_order' => 2, 'parent_id' => $sAdmin->id,
            ]);
            $this->upsert('menus', [
                'name' => 'Menus', 'route_name' => '/menus',
                'icon' => 'LayoutList', 'sort_order' => 3, 'parent_id' => $sAdmin->id,
            ]);
            $this->upsert('settings', [
                'name' => 'Settings', 'route_name' => '/settings',
                'icon' => 'Settings2', 'sort_order' => 4, 'parent_id' => $sAdmin->id,
            ]);
        });

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $admin = Role::findByName('admin', 'web');
        $admin->syncPermissions(Permission::where('guard_name', 'web')->get());

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    private function upsert(string $slug, array $attrs): Menu
    {
        $menu = Menu::firstOrNew(['slug' => $slug]);
        $menu->fill(array_merge($attrs, ['is_active' => true, 'slug' => $slug]));
        $menu->save();
        return $menu;
    }

    private function section(string $slug, string $name, int $sortOrder, ?string $icon = null): Menu
    {
        $menu = Menu::firstOrNew(['slug' => $slug]);
        $menu->fill([
            'slug'       => $slug,
            'name'       => $name,
            'route_name' => null,
            'icon'       => $icon,
            'parent_id'  => null,
            'sort_order' => $sortOrder,
            'is_active'  => true,
        ]);
        $menu->save();
        return $menu;
    }
}
