<?php

namespace App\Services;

use App\Models\SupplierReturn;
use Illuminate\Support\Facades\DB;

class SupplierReturnService
{
    public function __construct(
        private readonly StockMovementService  $stockService,
        private readonly SupplierLedgerService $ledgerService,
    ) {}

    /**
     * Confirm a supplier return.
     *
     * For each return item:
     *   - Decreases stock via stock_movement(supplier_return_out)
     * For the return as a whole:
     *   - Debits the supplier ledger (we return goods, so we owe them less)
     * Status transitions: draft → confirmed
     */
    public function confirm(SupplierReturn $return, int $userId): void
    {
        if (!$return->canBeConfirmed()) {
            throw new \RuntimeException(
                "Supplier return {$return->reference_number} cannot be confirmed (status: {$return->status})."
            );
        }

        DB::transaction(function () use ($return, $userId) {
            $items = $return->items()->get();

            if ($items->isEmpty()) {
                throw new \RuntimeException("Cannot confirm a return with no items.");
            }

            foreach ($items as $item) {
                $this->stockService->record(
                    productId:     (int) $item->product_id,
                    variantId:     $item->variant_id ? (int) $item->variant_id : null,
                    type:          'supplier_return_out',
                    quantity:      (float) $item->quantity,
                    costPrice:     (float) $item->cost_price,
                    referenceType: 'supplier_return',
                    referenceId:   $return->id,
                    notes:         "Return to supplier: {$return->reference_number}",
                    createdBy:     $userId,
                    date:          $return->return_date->toDateString(),
                );
            }

            $return->update(['status' => 'confirmed']);

            // Debit supplier ledger — returned goods reduce what we owe
            $this->ledgerService->debit(
                supplier:      $return->supplier,
                amount:        (float) $return->total_amount,
                referenceType: 'supplier_return',
                referenceId:   $return->id,
                description:   "Supplier return: {$return->reference_number}",
                createdBy:     $userId,
                date:          $return->return_date->toDateString(),
            );
        });
    }
}
