<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\SaleReturn;
use Illuminate\Support\Facades\DB;

class SaleReturnService
{
    public function __construct(
        private readonly StockMovementService $stockService,
    ) {}

    /**
     * Approve a customer sale return.
     *
     * For each return item:
     *   - Increases stock via stock_movement(customer_return_in)
     * Optional future hooks:
     *   - refund_type = 'cash'         → record in cash drawer
     *   - refund_type = 'wallet'        → credit customer.wallet_balance
     *   - refund_type = 'store_credit'  → issue store credit record
     *   - refund_type = 'replace'       → handled by creating a new sale externally
     * Status transitions: pending → approved
     */
    public function approve(SaleReturn $return, int $userId): void
    {
        if (!$return->isPending()) {
            throw new \RuntimeException(
                "Sale return {$return->reference_number} is not in pending status."
            );
        }

        DB::transaction(function () use ($return, $userId) {
            $items = $return->items()->get();

            if ($items->isEmpty()) {
                throw new \RuntimeException("Cannot approve a return with no items.");
            }

            foreach ($items as $item) {
                $this->stockService->record(
                    productId:     (int) $item->product_id,
                    variantId:     $item->variant_id ? (int) $item->variant_id : null,
                    type:          'customer_return_in',
                    quantity:      (float) $item->quantity,
                    referenceType: 'sale_return',
                    referenceId:   $return->id,
                    notes:         "Customer return: {$return->reference_number}",
                    createdBy:     $userId,
                    date:          $return->return_date->toDateString(),
                );
            }

            $return->update([
                'status'       => 'approved',
                'processed_by' => $userId,
            ]);

            // Handle wallet refund
            if ($return->refund_type === 'wallet' && $return->customer_id) {
                $this->creditWallet($return);
            }
        });
    }

    /**
     * Reject a pending return — no stock or ledger changes.
     */
    public function reject(SaleReturn $return, int $userId): void
    {
        if (!$return->isPending()) {
            throw new \RuntimeException("Only pending returns can be rejected.");
        }

        $return->update([
            'status'       => 'rejected',
            'processed_by' => $userId,
        ]);
    }

    private function creditWallet(SaleReturn $return): void
    {
        // Extend Customer with wallet_balance when ready; stub kept here for wiring
        Customer::where('id', $return->customer_id)
            ->increment('wallet_balance', (float) $return->refund_amount);
    }
}
