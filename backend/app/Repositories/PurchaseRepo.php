<?php

namespace App\Repositories;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchasePayment;
use App\Repositories\Concerns\RecordsStockMovements;
use App\Repositories\Concerns\UpdatesSupplierLedger;
use App\Services\AccountingService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseRepo
{
    use RecordsStockMovements, UpdatesSupplierLedger;

    public function __construct(private readonly AccountingService $accountingService) {}

    // ─── Listing ──────────────────────────────────────────────────────────────

    public function getData(Request $request): LengthAwarePaginator
    {
        $query = Purchase::with('supplier');

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('reference_number', 'LIKE', "%{$term}%")
                  ->orWhereHas('supplier', fn($s) => $s->where('name', 'LIKE', "%{$term}%"));
            });
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('date_from')) {
            $query->where('purchase_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('purchase_date', '<=', $request->date_to);
        }

        match ($request->input('sort', 'newest')) {
            'oldest'    => $query->oldest(),
            'amount'    => $query->orderBy('total_amount', 'desc'),
            default     => $query->latest('purchase_date'),
        };

        return $query->paginate($request->input('per_page', 15));
    }

    public function findWithDetails(int $id): Purchase
    {
        return Purchase::with(['supplier', 'items', 'payments.createdBy', 'createdBy'])
            ->findOrFail($id);
    }

    // ─── CRUD ─────────────────────────────────────────────────────────────────

    public function store(array $data, int $userId): Purchase
    {
        return DB::transaction(function () use ($data, $userId): Purchase {
            $items = $data['items'];
            unset($data['items']);

            $data['created_by'] = $userId;
            $data['status']     = $data['status'] ?? 'draft';

            [$subtotal, $taxAmount, $discountAmount] = $this->calculateItemTotals($items);
            $shippingCost = (float) ($data['shipping_cost'] ?? 0);
            $totalAmount  = $subtotal + $taxAmount + $shippingCost - $discountAmount;

            $purchase = Purchase::create(array_merge($data, [
                'subtotal'         => $subtotal,
                'tax_amount'       => $taxAmount,
                'discount_amount'  => $discountAmount,
                'total_amount'     => $totalAmount,
                'due_amount'       => $totalAmount,
            ]));

            $this->createItems($purchase, $items);

            return $this->findWithDetails($purchase->id);
        });
    }

    public function update(Purchase $purchase, array $data, int $userId): Purchase
    {
        if (!in_array($purchase->status, ['draft', 'ordered'])) {
            abort(422, 'Only draft or ordered purchases can be edited.');
        }

        return DB::transaction(function () use ($purchase, $data): Purchase {
            if (isset($data['items'])) {
                $purchase->items()->delete();
                [$subtotal, $taxAmount, $discountAmount] = $this->calculateItemTotals($data['items']);
                $shippingCost = (float) ($data['shipping_cost'] ?? $purchase->shipping_cost);
                $totalAmount  = $subtotal + $taxAmount + $shippingCost - $discountAmount;

                $data = array_merge($data, [
                    'subtotal'        => $subtotal,
                    'tax_amount'      => $taxAmount,
                    'discount_amount' => $discountAmount,
                    'total_amount'    => $totalAmount,
                    'due_amount'      => $totalAmount - (float) $purchase->paid_amount,
                ]);

                $this->createItems($purchase, $data['items']);
                unset($data['items']);
            }

            $purchase->update($data);

            return $this->findWithDetails($purchase->id);
        });
    }

    public function destroy(Purchase $purchase): void
    {
        if ($purchase->status !== 'draft') {
            abort(422, 'Only draft purchases can be deleted.');
        }

        $purchase->items()->delete();
        $purchase->delete();
    }

    // ─── Receive Items ────────────────────────────────────────────────────────

    public function receiveItems(Purchase $purchase, array $items, int $userId): Purchase
    {
        if ($purchase->status === 'received') {
            abort(422, "Purchase {$purchase->reference_number} is already fully received.");
        }

        DB::transaction(function () use ($purchase, $items, $userId): void {
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
                    abort(422, "Cannot receive {$qty} of \"{$item->product_name}\"; only {$maxReceivable} remaining.");
                }

                $this->recordMovement(
                    productId:     (int) $item->product_id,
                    variantId:     $item->variant_id ? (int) $item->variant_id : null,
                    type:          'purchase_in',
                    quantity:      $qty,
                    costPrice:     (float) $item->cost_price,
                    referenceType: 'purchase',
                    referenceId:   $purchase->id,
                    notes:         "Received: {$purchase->reference_number}",
                    createdBy:     $userId,
                    date:          now()->toDateString(),
                );

                $newReceived = (float) $item->quantity_received + $qty;
                $item->update([
                    'quantity_received' => $newReceived,
                    'subtotal'          => (float) $item->cost_price * $newReceived,
                ]);

                $receiptValue += (float) $item->cost_price * $qty;
            }

            $hasUnreceived = $purchase->items()
                ->whereColumn('quantity_received', '<', 'quantity_ordered')
                ->exists();

            $purchase->update([
                'status'        => $hasUnreceived ? 'partial' : 'received',
                'received_date' => $hasUnreceived ? null : now()->toDateString(),
            ]);

            if ($receiptValue > 0) {
                $this->creditSupplier(
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

        return $this->findWithDetails($purchase->id);
    }

    // ─── Payments ─────────────────────────────────────────────────────────────

    public function storePayment(Purchase $purchase, array $data, int $userId): PurchasePayment
    {
        $amount = (float) $data['amount'];

        if ($amount > (float) $purchase->due_amount) {
            abort(422, "Payment {$amount} exceeds due amount {$purchase->due_amount}.");
        }

        return DB::transaction(function () use ($purchase, $data, $amount, $userId): PurchasePayment {
            /** @var PurchasePayment $payment */
            $payment = $purchase->payments()->create([
                'amount'         => $amount,
                'payment_method' => $data['payment_method'],
                'payment_date'   => $data['payment_date'],
                'reference'      => $data['reference'] ?? null,
                'notes'          => $data['notes'] ?? null,
                'created_by'     => $userId,
            ]);

            $newPaid = (float) $purchase->paid_amount + $amount;
            $newDue  = max(0, (float) $purchase->total_amount - $newPaid);

            $purchase->update([
                'paid_amount'    => $newPaid,
                'due_amount'     => $newDue,
                'payment_status' => $newDue < 0.01 ? 'paid' : 'partial',
            ]);

            $this->debitSupplier(
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

    // ─── Private ──────────────────────────────────────────────────────────────

    private function createItems(Purchase $purchase, array $items): void
    {
        foreach ($items as $item) {
            $product    = Product::find($item['product_id']);
            $variantId  = $item['variant_id'] ?? null;
            $productSku = $variantId
                ? ProductVariant::find($variantId)?->sku
                : $product?->sku;

            $lineSubtotal = (float) $item['cost_price'] * (float) $item['quantity_ordered'];

            $purchase->items()->create([
                'product_id'       => $item['product_id'],
                'variant_id'       => $variantId,
                'product_name'     => $product?->name ?? 'Unknown',
                'product_sku'      => $productSku,
                'quantity_ordered' => $item['quantity_ordered'],
                'quantity_received'=> 0,
                'cost_price'       => $item['cost_price'],
                'tax_rate'         => $item['tax_rate'] ?? 0,
                'discount_amount'  => $item['discount_amount'] ?? 0,
                'subtotal'         => $lineSubtotal,
            ]);
        }
    }

    private function calculateItemTotals(array $items): array
    {
        $subtotal       = 0.0;
        $taxAmount      = 0.0;
        $discountAmount = 0.0;

        foreach ($items as $item) {
            $lineTotal       = (float) $item['cost_price'] * (float) $item['quantity_ordered'];
            $subtotal       += $lineTotal;
            $taxAmount      += $lineTotal * ((float) ($item['tax_rate'] ?? 0) / 100);
            $discountAmount += (float) ($item['discount_amount'] ?? 0);
        }

        return [$subtotal, $taxAmount, $discountAmount];
    }
}
