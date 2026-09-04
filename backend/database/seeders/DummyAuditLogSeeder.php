<?php

namespace Database\Seeders;

use App\Models\Administration\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DummyAuditLogSeeder extends Seeder
{
    private array $forms = [
        'Product', 'Category', 'Brand', 'Customer', 'Supplier', 'Purchase',
        'Order', 'AlterationOrder', 'Tailor', 'MeasurementType', 'GarmentPrice',
        'AlterationType', 'Expense', 'User', 'Role', 'Menu', 'StoreSetting',
    ];

    private array $actions = ['view', 'create', 'edit', 'delete'];

    private array $browsers = ['Chrome', 'Firefox', 'Safari', 'Edge'];

    public function run(): void
    {
        $faker = \Faker\Factory::create();
        $userIds = User::pluck('id')->all();

        if (empty($userIds)) {
            $this->command?->warn('No users found — skipping dummy audit logs.');
            return;
        }

        $now = Carbon::now();
        $rows = [];

        for ($i = 0; $i < 1000; $i++) {
            $form = $faker->randomElement($this->forms);
            $action = $faker->randomElement($this->actions);
            $createdAt = $now->copy()->subDays(random_int(0, 90))->subSeconds(random_int(0, 86400));

            $rows[] = [
                'user_id'    => $faker->randomElement($userIds),
                'action'     => $action,
                'form'       => $form,
                'record_id'  => random_int(1, 500),
                'record'     => $faker->words(random_int(2, 4), true),
                'ip'         => $faker->ipv4(),
                'browser'    => $faker->randomElement($this->browsers),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];

            if (count($rows) === 200) {
                DB::table('audit_logs')->insert($rows);
                $rows = [];
            }
        }

        if (!empty($rows)) {
            DB::table('audit_logs')->insert($rows);
        }

        $this->command?->info('Seeded 1000 dummy audit logs.');
    }
}
