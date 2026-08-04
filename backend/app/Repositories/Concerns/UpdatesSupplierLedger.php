<?php

namespace App\Repositories\Concerns;

use App\Models\Supplier;
use App\Models\SupplierLedgerEntry;

trait UpdatesSupplierLedger
{
    /**
     * Post a credit entry — supplier balance increases (we owe them more).
     * Must be called inside an existing DB::transaction().
     */
    protected function creditSupplier(
        Supplier $supplier,
        float    $amount,
        string   $referenceType,
        int      $referenceId,
        string   $description,
        int      $createdBy,
        ?string  $date = null
    ): SupplierLedgerEntry {
        return $this->postLedger($supplier, 'credit', $amount, $referenceType, $referenceId, $description, $createdBy, $date);
    }

    /**
     * Post a debit entry — supplier balance decreases (we owe them less).
     * Must be called inside an existing DB::transaction().
     */
    protected function debitSupplier(
        Supplier $supplier,
        float    $amount,
        string   $referenceType,
        int      $referenceId,
        string   $description,
        int      $createdBy,
        ?string  $date = null
    ): SupplierLedgerEntry {
        return $this->postLedger($supplier, 'debit', $amount, $referenceType, $referenceId, $description, $createdBy, $date);
    }

    private function postLedger(
        Supplier $supplier,
        string   $type,
        float    $amount,
        string   $referenceType,
        int      $referenceId,
        string   $description,
        int      $createdBy,
        ?string  $date
    ): SupplierLedgerEntry {
        // Re-fetch with lock so concurrent requests can't race on the balance
        $supplier = Supplier::lockForUpdate()->findOrFail($supplier->id);

        $newBalance = $type === 'credit'
            ? (float) $supplier->current_balance + $amount
            : (float) $supplier->current_balance - $amount;

        $entry = SupplierLedgerEntry::create([
            'supplier_id'    => $supplier->id,
            'type'           => $type,
            'amount'         => $amount,
            'balance_after'  => $newBalance,
            'reference_type' => $referenceType,
            'reference_id'   => $referenceId,
            'date'           => $date ?? now()->toDateString(),
            'description'    => $description,
            'created_by'     => $createdBy,
        ]);

        $supplier->update(['current_balance' => $newBalance]);

        return $entry;
    }
}
