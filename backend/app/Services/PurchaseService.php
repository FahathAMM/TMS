<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchasePayment;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function __construct(
        private readonly StockMovementService  $stockService,
        private readonly SupplierLedgerService $ledgerService,
        private readonly AccountingService     $accountingService,
    ) {}

    /**
     * Receive items against a purchase order.
     *
     * $items = [
     *   ['purchase_item_id' => 1, 'quantity_received' => 5.000],
     *   ...
     * ]
     *
     * Each item triggers a stock_movement(purchase_in) and updates the line's
     * quantity_received. When all lines are fully received the PO status
     * becomes 'received'; partial receipt sets 'partial'.
     *
     * The supplier ledger is credited once for the total value of this receipt.
     */
    public function receiveItems(Purchase $purchase, array $items, int $userId): void
    {
        if ($purchase->status === 'received') {
            throw new \RuntimeException("Purchase {$purchase->reference_number} is already fully received.");
        }

        DB::transaction(function () use ($purchase, $items, $userId) {
            $receiptValue = 0.0;

            foreach ($items as $itemData) {
                $qty = (float) $itemData['quantity_received'];
                if ($qty <= 0) {
                    continue;
                }

                /** @var PurchaseItem $item */
                $item = PurchaseItem::where('purchase_id', $purchase->id)
                    ->findOrFail($itemData['purchase_item_id']);

                $maxReceivable = (float) $item->quantity_ordered - (float) $item->quantity_received;
                if ($qty > $maxReceivable) {
                    throw new \RuntimeException(
                        "Cannot receive {$qty} of \"{$item->product_name}\"; only {$maxReceivable} remaining."
                    );
                }

                // Record stock movement
                $this->stockService->record(
                    productId:     (int) $item->product_id,
                    variantId:     $item->variant_id ? (int) $item->variant_id : null,
                    type:          'purchase_in',
                    quantity:      $qty,
                    costPrice:     (float) $item->cost_price,
                    referenceType: 'purchase',
                    referenceId:   $purchase->id,
                    notes:         "Received against {$purchase->reference_number}",
                    createdBy:     $userId,
                    date:          now()->toDateString(),
                );

                $item->increment('quantity_received', $qty);
                // Update item subtotal to reflect received quantity
                $item->update(['subtotal' => $item->cost_price * $item->quantity_received]);

                $receiptValue += (float) $item->cost_price * $qty;
            }

            // Determine new purchase status
            $hasUnreceived = $purchase->items()
                ->whereColumn('quantity_received', '<', 'quantity_ordered')
                ->exists();

            $purchase->update([
                'status'        => $hasUnreceived ? 'partial' : 'received',
                'received_date' => $hasUnreceived ? null : now()->toDateString(),
            ]);

            // Credit supplier ledger for the value received
            if ($receiptValue > 0) {
                $this->ledgerService->credit(
                    supplier:      $purchase->supplier,
                    amount:        $receiptValue,
                    referenceType: 'purchase',
                    referenceId:   $purchase->id,
                    description:   "Goods received: {$purchase->reference_number}",
                    createdBy:     $userId,
                );

                // Books: raw material stock increases, we owe the supplier more.
                $this->accountingService->postEntry(
                    description:   "Goods received: {$purchase->reference_number}",
                    lines: [
                        ['account' => AccountingService::INVENTORY_ASSET,  'debit'  => $receiptValue],
                        ['account' => AccountingService::ACCOUNTS_PAYABLE, 'credit' => $receiptValue],
                    ],
                    referenceType: 'purchase',
                    referenceId:   $purchase->id,
                    createdBy:     $userId,
                );
            }
        });
    }

    /**
     * Record a payment against a purchase.
     * Updates paid_amount, due_amount, payment_status, and debits the supplier ledger.
     */
    public function recordPayment(Purchase $purchase, array $data, int $userId): PurchasePayment
    {
        return DB::transaction(function () use ($purchase, $data, $userId) {
            $amount = (float) $data['amount'];

            if ($amount > (float) $purchase->due_amount) {
                throw new \RuntimeException(
                    "Payment {$amount} exceeds due amount {$purchase->due_amount} on {$purchase->reference_number}."
                );
            }

            /** @var PurchasePayment $payment */
            $payment = $purchase->payments()->create([
                'amount'         => $amount,
                'payment_method' => $data['payment_method'],
                'payment_date'   => $data['payment_date'] ?? now()->toDateString(),
                'reference'      => $data['reference'] ?? null,
                'notes'          => $data['notes'] ?? null,
                'created_by'     => $userId,
            ]);

            $newPaid = (float) $purchase->paid_amount + $amount;
            $newDue  = (float) $purchase->total_amount - $newPaid;

            $purchase->update([
                'paid_amount'    => $newPaid,
                'due_amount'     => max(0, $newDue),
                'payment_status' => $newDue <= 0.001 ? 'paid' : 'partial',
            ]);

            // Debit supplier ledger — we paid, so we owe less
            $this->ledgerService->debit(
                supplier:      $purchase->supplier,
                amount:        $amount,
                referenceType: 'payment',
                referenceId:   $payment->id,
                description:   "Payment for {$purchase->reference_number}",
                createdBy:     $userId,
                date:          $payment->payment_date->toDateString(),
            );

            return $payment;
        });
    }
}
