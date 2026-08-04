<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Core catalogue
            'view products',   'create products',   'edit products',   'delete products',
            'view categories', 'create categories', 'edit categories', 'delete categories',
            'view brands',     'create brands',     'edit brands',     'delete brands',

            // Customers & Sales
            'view customers',  'create customers',  'edit customers',  'delete customers',
            'view sales',      'create sales',      'cancel sales',

            // Inventory
            'view inventory',  'adjust inventory',

            // Procurement
            'view suppliers',          'create suppliers',          'edit suppliers',          'delete suppliers',
            'view purchases',          'create purchases',          'edit purchases',          'delete purchases',
            'view supplier_returns',   'create supplier_returns',   'edit supplier_returns',   'delete supplier_returns',

            // Returns
            'view sale_returns',       'create sale_returns',       'edit sale_returns',       'delete sale_returns',

            // Admin
            'view dashboard',
            'view users',      'create users',      'edit users',      'delete users',

            // Tailoring
            'view tailoring_orders',  'create tailoring_orders',  'edit tailoring_orders',
            'view production',
            'view tailors',           'create tailors',           'edit tailors',           'delete tailors',
            'view measurement_types', 'create measurement_types', 'edit measurement_types', 'delete measurement_types',
            'view stock_movements',
            'view expenses', 'create expenses',
            'view reports',
            'view garment_prices', 'create garment_prices', 'edit garment_prices', 'delete garment_prices',

            // Accounting (admin-only — no staff sync below)
            'view accounting_accounts', 'view accounting_journal', 'view accounting_reports',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Admin — all permissions
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::where('guard_name', 'web')->get());

        // Staff — limited permissions
        $staff = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        $staff->syncPermissions([
            'view products',    'create products',  'edit products',
            'view categories',
            'view brands',
            'view customers',   'create customers', 'edit customers',
            'view sales',       'create sales',
            'view inventory',   'adjust inventory',
            // Procurement — view + create; no delete
            'view suppliers',
            'view purchases',   'create purchases', 'edit purchases',
            'view supplier_returns',
            // Sale returns — staff can initiate and process
            'view sale_returns', 'create sale_returns', 'edit sale_returns',
            'view dashboard',

            // Tailoring
            'view tailoring_orders', 'create tailoring_orders', 'edit tailoring_orders',
            'view production',
            'view tailors',          'create tailors',          'edit tailors',
            'view measurement_types', 'create measurement_types', 'edit measurement_types',
            'view stock_movements',
            'view expenses', 'create expenses',
            'view reports',
            'view garment_prices', 'create garment_prices', 'edit garment_prices',
        ]);
    }
}
