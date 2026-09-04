<?php

namespace Database\Seeders;

use App\Models\Accounting\Account;
use App\Services\AccountingService;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['code' => AccountingService::CASH,             'name' => 'Cash / Bank',          'type' => 'asset',     'normal_balance' => 'debit'],
            ['code' => AccountingService::INVENTORY_ASSET,  'name' => 'Inventory Asset',      'type' => 'asset',     'normal_balance' => 'debit'],
            ['code' => AccountingService::WIP,               'name' => 'Work-in-Progress',     'type' => 'asset',     'normal_balance' => 'debit'],
            ['code' => AccountingService::UNEARNED_REVENUE, 'name' => 'Unearned Revenue',     'type' => 'liability', 'normal_balance' => 'credit'],
            ['code' => AccountingService::ACCOUNTS_PAYABLE, 'name' => 'Accounts Payable',     'type' => 'liability', 'normal_balance' => 'credit'],
            ['code' => AccountingService::SALES_REVENUE,    'name' => 'Tailoring Sales Revenue', 'type' => 'revenue', 'normal_balance' => 'credit'],
            ['code' => AccountingService::COGS,              'name' => 'Cost of Goods Sold',   'type' => 'expense',   'normal_balance' => 'debit'],
            ['code' => AccountingService::EXPENSES,          'name' => 'Operating Expenses',   'type' => 'expense',   'normal_balance' => 'debit'],
            ['code' => AccountingService::ALTERATION_REVENUE,'name' => 'Alteration Revenue',   'type' => 'revenue',   'normal_balance' => 'credit'],
        ];

        foreach ($accounts as $account) {
            Account::firstOrCreate(['code' => $account['code']], $account);
        }
    }
}
