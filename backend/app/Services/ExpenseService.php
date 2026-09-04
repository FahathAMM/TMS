<?php

namespace App\Services;

use App\Models\Accounting\Expense;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    public function __construct(private readonly AccountingService $accountingService) {}

    public function record(array $data, ?int $userId): Expense
    {
        return DB::transaction(function () use ($data, $userId) {
            /** @var Expense $expense */
            $expense = Expense::create([
                'category'       => $data['category'],
                'description'    => $data['description'] ?? null,
                'amount'         => $data['amount'],
                'expense_date'   => $data['expense_date'],
                'payment_method' => $data['payment_method'] ?? 'cash',
                'created_by'     => $userId,
            ]);

            $this->accountingService->postEntry(
                description:   "Expense: {$expense->category} ({$expense->expense_number})",
                lines: [
                    ['account' => AccountingService::EXPENSES, 'debit'  => (float) $expense->amount],
                    ['account' => AccountingService::CASH,     'credit' => (float) $expense->amount],
                ],
                referenceType: 'expense',
                referenceId:   $expense->id,
                createdBy:     $userId,
                date:          $expense->expense_date->toDateString(),
            );

            return $expense;
        });
    }
}
