<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Product;
use App\Models\StockAdjustment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function __construct(private readonly OfferService $offerService) {}

    public function getAll(array $filters = [])
    {
        $query = Sale::with(['customer', 'user', 'items.product'])
            ->latest('sold_at');

        if (!empty($filters['search'])) {
            $query->where('invoice_no', 'like', "%{$filters['search']}%");
        }

        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('sold_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('sold_at', '<=', $filters['date_to']);
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function create(array $data, int $userId): Sale
    {
        return DB::transaction(function () use ($data, $userId) {
            $this->validateStock($data['items']);

            // Re-apply active offers server-side so discounts can't be forged by the client
            $activeOffers   = $this->offerService->getActiveOffers();
            $data['items']  = $this->offerService->applyToCart($data['items'], $activeOffers);

            $subtotal = 0;
            $itemsToCreate = [];

            foreach ($data['items'] as $item) {
                $product = Product::lockForUpdate()->find($item['product_id']);
                $lineTotal = ($item['unit_price'] * $item['quantity']) - ($item['discount'] ?? 0);
                $subtotal += $lineTotal;

                $itemsToCreate[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'cost_price' => $product->cost_price,
                    'discount' => $item['discount'] ?? 0,
                    'total' => $lineTotal,
                ];
            }

            $discountAmount = $data['discount_amount'] ?? 0;
            $taxAmount = $data['tax_amount'] ?? 0;
            $totalAmount = $subtotal - $discountAmount + $taxAmount;
            $paidAmount = $data['paid_amount'];
            $changeAmount = $paidAmount - $totalAmount;

            $sale = Sale::create([
                'invoice_no' => $this->generateInvoiceNo(),
                'customer_id' => $data['customer_id'] ?? null,
                'user_id' => $userId,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'change_amount' => max(0, $changeAmount),
                'payment_method' => $data['payment_method'] ?? 'cash',
                'status' => 'completed',
                'notes' => $data['notes'] ?? null,
                'sold_at' => now(),
            ]);

            $sale->items()->createMany($itemsToCreate);

            foreach ($data['items'] as $item) {
                $product = Product::find($item['product_id']);
                $before = $product->stock_quantity;
                $product->decrement('stock_quantity', $item['quantity']);

                StockAdjustment::create([
                    'product_id' => $product->id,
                    'user_id' => $userId,
                    'type' => 'sale',
                    'quantity_before' => $before,
                    'quantity_changed' => -$item['quantity'],
                    'quantity_after' => $before - $item['quantity'],
                    'reason' => "Sale #{$sale->invoice_no}",
                    'sale_id' => $sale->id,
                ]);
            }

            return $sale->load(['customer', 'user', 'items.product']);
        });
    }

    public function cancel(Sale $sale, int $userId): Sale
    {
        if ($sale->status === 'cancelled') {
            throw ValidationException::withMessages(['sale' => ['Sale is already cancelled.']]);
        }

        return DB::transaction(function () use ($sale, $userId) {
            foreach ($sale->items as $item) {
                $product = Product::lockForUpdate()->find($item->product_id);
                $before = $product->stock_quantity;
                $product->increment('stock_quantity', $item->quantity);

                StockAdjustment::create([
                    'product_id' => $product->id,
                    'user_id' => $userId,
                    'type' => 'return',
                    'quantity_before' => $before,
                    'quantity_changed' => $item->quantity,
                    'quantity_after' => $before + $item->quantity,
                    'reason' => "Cancelled Sale #{$sale->invoice_no}",
                    'sale_id' => $sale->id,
                ]);
            }

            $sale->update(['status' => 'cancelled']);
            return $sale->fresh(['customer', 'user', 'items.product']);
        });
    }

    private function validateStock(array $items): void
    {
        foreach ($items as $item) {
            $product = Product::find($item['product_id']);
            if (!$product) {
                throw ValidationException::withMessages(['items' => ["Product #{$item['product_id']} not found."]]);
            }
            if ($product->stock_quantity < $item['quantity']) {
                throw ValidationException::withMessages([
                    'items' => ["Insufficient stock for {$product->name}. Available: {$product->stock_quantity}"],
                ]);
            }
        }
    }

    private function generateInvoiceNo(): string
    {
        $prefix = 'INV-' . date('Ymd') . '-';
        $last = Sale::where('invoice_no', 'like', $prefix . '%')->latest()->first();
        $sequence = $last ? ((int) substr($last->invoice_no, -4)) + 1 : 1;
        return $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
}
