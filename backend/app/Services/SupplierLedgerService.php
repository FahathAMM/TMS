<?php

namespace App\Services;

use App\Models\Supplier;
use App\Models\SupplierLedgerEntry;
use Illuminate\Support\Facades\DB;

class SupplierLedgerService
{
    /**
     * Credit the supplier — we received goods / owe them more.
     */
    public function credit(
        Supplier $supplier,
        float    $amount,
        ?string  $referenceType = null,
        ?int     $referenceId   = null,
        ?string  $description   = null,
        ?int     $createdBy     = null,
        ?string  $date          = null
    ): SupplierLedgerEntry {
        return $this->post($supplier, 'credit', $amount, $referenceType, $referenceId, $description, $createdBy, $date);
    }

    /**
     * Debit the supplier — we paid them / returned goods, reducing what we owe.
     */
    public function debit(
        Supplier $supplier,
        float    $amount,
        ?string  $referenceType = null,
        ?int     $referenceId   = null,
        ?string  $description   = null,
        ?int     $createdBy     = null,
        ?string  $date          = null
    ): SupplierLedgerEntry {
        return $this->post($supplier, 'debit', $amount, $referenceType, $referenceId, $description, $createdBy, $date);
    }

    private function post(
        Supplier $supplier,
        string   $type,
        float    $amount,
        ?string  $referenceType,
        ?int     $referenceId,
        ?string  $description,
        ?int     $createdBy,
        ?string  $date
    ): SupplierLedgerEntry {
        return DB::transaction(function () use ($supplier, $type, $amount, $referenceType, $referenceId, $description, $createdBy, $date) {
            // Lock supplier row to prevent balance race conditions
            $supplier = Supplier::lockForUpdate()->findOrFail($supplier->id);

            $newBalance = $type === 'credit'
                ? $supplier->current_balance + $amount
                : $supplier->current_balance - $amount;

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
        });
    }
}
