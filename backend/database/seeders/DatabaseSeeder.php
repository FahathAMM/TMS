<?php

namespace Database\Seeders;

use App\Models\Administration\User;
use App\Models\Customers\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seed menu structure (also creates the admin/staff/super-admin
        // roles, nav-visibility permissions, and syncs the admin role)
        $this->call(MenuSeeder::class);

        // Chart of accounts (double-entry ledger) and measurement lookups
        $this->call(AccountSeeder::class);
        $this->call(MeasurementTypeSeeder::class);
        $this->call(AlterationTypeSeeder::class);

        // Store settings (key-value, cached)
        $this->call(StoreSettingSeeder::class);

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

        // Fahath — admin account
        $fahath = User::firstOrCreate(
            ['email' => 'fahathammex90@gmail.com'],
            ['name' => 'Fahath', 'password' => Hash::make('fahath@123'), 'is_active' => true]
        );
        $fahath->syncRoles([Role::findByName('admin', 'web')]);

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

        // Tailors available for assignment, plus demo custom-stitching orders
        // and alteration orders so the tailoring flows have sample data.
        $this->call(TailorSeeder::class);
    }
}
