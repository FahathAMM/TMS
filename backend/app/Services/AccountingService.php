<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AccountingService
{
    // Chart of accounts codes — seeded by AccountSeeder.
    public const CASH              = 'CASH';
    public const INVENTORY_ASSET   = 'INVENTORY';
    public const WIP                = 'WIP';
    public const UNEARNED_REVENUE  = 'UNEARNED_REV';
    public const ACCOUNTS_PAYABLE  = 'AP';
    public const SALES_REVENUE     = 'SALES_REV';
    public const COGS              = 'COGS';
    public const EXPENSES          = 'EXPENSES';
    public const ALTERATION_REVENUE = 'ALTERATION_REV';

    /**
     * Post a balanced double-entry journal entry.
     *
     * @param array<int, array{account: string, debit?: float, credit?: float}> $lines
     */
    public function postEntry(
        string  $description,
        array   $lines,
        ?string $referenceType = null,
        ?int    $referenceId   = null,
        ?int    $createdBy     = null,
        ?string $date          = null,
    ): JournalEntry {
        // Drop zero-value lines (e.g. an optional deposit-clearing leg of 0)
        $lines = array_values(array_filter($lines, fn ($l) => (float) ($l['debit'] ?? 0) > 0 || (float) ($l['credit'] ?? 0) > 0));

        if (count($lines) < 2) {
            throw new InvalidArgumentException('A journal entry needs at least two non-zero lines.');
        }

        $totalDebit  = round(array_sum(array_map(fn ($l) => (float) ($l['debit'] ?? 0), $lines)), 2);
        $totalCredit = round(array_sum(array_map(fn ($l) => (float) ($l['credit'] ?? 0), $lines)), 2);

        if ($totalDebit !== $totalCredit) {
            throw new InvalidArgumentException("Journal entry does not balance: debit {$totalDebit} vs credit {$totalCredit}.");
        }

        return DB::transaction(function () use ($description, $lines, $referenceType, $referenceId, $createdBy, $date) {
            $entry = JournalEntry::create([
                'entry_date'     => $date ?? now()->toDateString(),
                'description'    => $description,
                'reference_type' => $referenceType,
                'reference_id'   => $referenceId,
                'created_by'     => $createdBy,
            ]);

            foreach ($lines as $line) {
                $account = Account::where('code', $line['account'])->firstOrFail();

                $entry->lines()->create([
                    'account_id' => $account->id,
                    'debit'      => $line['debit'] ?? 0,
                    'credit'     => $line['credit'] ?? 0,
                ]);
            }

            return $entry->load('lines.account');
        });
    }

    /**
     * Balance of every account (signed per its normal balance).
     *
     * @return array<int, array{code: string, name: string, type: string, balance: float}>
     */
    public function trialBalance(): array
    {
        return Account::all()->map(fn (Account $account) => [
            'code'    => $account->code,
            'name'    => $account->name,
            'type'    => $account->type->value,
            'balance' => $account->balance,
        ])->all();
    }

    /**
     * Simple period P&L: sum of revenue accounts minus sum of expense accounts.
     */
    public function profitLoss(?string $from = null, ?string $to = null): array
    {
        $lineQuery = fn (string $code) => DB::table('journal_entry_lines')
            ->join('accounts', 'accounts.id', '=', 'journal_entry_lines.account_id')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('accounts.code', $code)
            ->when($from, fn ($q) => $q->where('journal_entries.entry_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('journal_entries.entry_date', '<=', $to));

        $revenue = (float) $lineQuery(self::SALES_REVENUE)->sum('credit') - (float) $lineQuery(self::SALES_REVENUE)->sum('debit');
        $cogs    = (float) $lineQuery(self::COGS)->sum('debit') - (float) $lineQuery(self::COGS)->sum('credit');

        return [
            'revenue'      => round($revenue, 2),
            'cogs'         => round($cogs, 2),
            'gross_profit' => round($revenue - $cogs, 2),
        ];
    }
}
