<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seed roles and permissions first
        $this->call(RolesAndPermissionsSeeder::class);

        // Seed menu structure (creates nav-visibility permissions, syncs admin role)
        $this->call(MenuSeeder::class);

        // Chart of accounts (double-entry ledger) and measurement lookups
        $this->call(AccountSeeder::class);
        $this->call(MeasurementTypeSeeder::class);

        // Store settings (key-value, cached)
        $this->call(StoreSettingSeeder::class);

        // Attributes and Tags
        $this->call(AttributeSeeder::class);
        $this->call(ProductAttributesSeeder::class);
        $this->call(ProductAttributeValuesSeeder::class);
        $this->call(TagSeeder::class);
        $this->call(TagsSeeder::class);

        // Admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@pos.com'],
            ['name' => 'Admin User', 'password' => Hash::make('password'), 'is_active' => true]
        );
        $admin->syncRoles([Role::findByName('admin', 'web')]);

        // Staff user
        $staff = User::firstOrCreate(
            ['email' => 'staff@pos.com'],
            ['name' => 'Staff User', 'password' => Hash::make('password'), 'is_active' => true]
        );
        $staff->syncRoles([Role::findByName('staff', 'web')]);

        // Fabric & trim catalogue (categories, brands, products) — replaces the
        // Mobile Shop POS electronics seed data from the original scaffold.
        $this->call(FabricCatalogSeeder::class);

        // Customers
        $customers = [
            ['name' => 'John Doe', 'mobile' => '0501234567', 'address' => '123 Main St'],
            ['name' => 'Jane Smith', 'mobile' => '0507654321', 'address' => '456 Oak Ave'],
            ['name' => 'Ali Hassan', 'mobile' => '0509876543', 'address' => '789 Palm Rd'],
        ];

        foreach ($customers as $customer) {
            Customer::firstOrCreate(['mobile' => $customer['mobile']], $customer);
        }
    }
}
