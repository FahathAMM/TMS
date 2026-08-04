<?php

namespace App\Repositories;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SaleReturn;
use App\Repositories\Concerns\RecordsStockMovements;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleReturnRepo
{
    use RecordsStockMovements;

    // ─── Listing ──────────────────────────────────────────────────────────────

    public function getData(Request $request): LengthAwarePaginator
    {
        $query = SaleReturn::with(['customer', 'sale']);

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('reference_number', 'LIKE', "%{$term}%")
                  ->orWhereHas('customer', fn($c) => $c->where('name', 'LIKE', "%{$term}%"));
            });
        }

        if ($request->filled('sale_id')) {
            $query->where('sale_id', $request->sale_id);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('refund_type')) {
            $query->where('refund_type', $request->refund_type);
        }

        if ($request->filled('date_from')) {
            $query->where('return_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('return_date', '<=', $request->date_to);
        }

        return $query->latest('return_date')->paginate($request->input('per_page', 15));
    }

    public function findWithDetails(int $id): SaleReturn
    {
        return SaleReturn::with(['customer', 'sale', 'items', 'processedBy'])
            ->findOrFail($id);
    }

    // ─── CRUD ─────────────────────────────────────────────────────────────────

    public function store(array $data, int $userId): SaleReturn
    {
        return DB::transaction(function () use ($data, $userId): SaleReturn {
            $items = $data['items'];
            unset($data['items']);

            $subtotal = collect($items)->sum(fn($i) => (float) $i['unit_price'] * (float) $i['quantity']);

            $return = SaleReturn::create(array_merge($data, [
                'subtotal'      => $subtotal,
                'total_amount'  => $subtotal,
                'refund_amount' => $subtotal,
                'status'        => 'pending',
            ]));

            $this->createItems($return, $items);

            return $this->findWithDetails($return->id);
        });
    }

    public function update(SaleReturn $return, array $data): SaleReturn
    {
        if (!$return->isPending()) {
            abort(422, 'Only pending returns can be edited.');
        }

        return DB::transaction(function () use ($return, $data): SaleReturn {
            if (isset($data['items'])) {
                $return->items()->delete();
                $subtotal = collect($data['items'])->sum(fn($i) => (float) $i['unit_price'] * (float) $i['quantity']);
                $data['subtotal']      = $subtotal;
                $data['total_amount']  = $subtotal;
                $data['refund_amount'] = $subtotal;
                $this->createItems($return, $data['items']);
                unset($data['items']);
            }

            $return->update($data);

            return $this->findWithDetails($return->id);
        });
    }

    public function destroy(SaleReturn $return): void
    {
        if (!$return->isPending()) {
            abort(422, 'Only pending returns can be deleted.');
        }

        DB::transaction(function () use ($return): void {
            $return->items()->delete();
            $return->delete();
        });
    }

    // ─── Approve / Reject ─────────────────────────────────────────────────────

    public function approve(SaleReturn $return, int $userId): SaleReturn
    {
        if (!$return->isPending()) {
            abort(422, "Return {$return->reference_number} is not in pending status.");
        }

        DB::transaction(function () use ($return, $userId): void {
            foreach ($return->items()->get() as $item) {
                $this->recordMovement(
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
        });

        return $this->findWithDetails($return->id);
    }

    public function reject(SaleReturn $return, int $userId): SaleReturn
    {
        if (!$return->isPending()) {
            abort(422, 'Only pending returns can be rejected.');
        }

        $return->update([
            'status'       => 'rejected',
            'processed_by' => $userId,
        ]);

        return $this->findWithDetails($return->id);
    }

    // ─── Private ──────────────────────────────────────────────────────────────

    private function createItems(SaleReturn $return, array $items): void
    {
        foreach ($items as $item) {
            $product   = Product::find($item['product_id']);
            $variantId = $item['variant_id'] ?? null;
            $sku       = $variantId ? ProductVariant::find($variantId)?->sku : $product?->sku;

            $return->items()->create([
                'product_id'    => $item['product_id'],
                'variant_id'    => $variantId,
                'product_name'  => $product?->name ?? 'Unknown',
                'product_sku'   => $sku,
                'quantity'      => $item['quantity'],
                'unit_price'    => $item['unit_price'],
                'subtotal'      => (float) $item['unit_price'] * (float) $item['quantity'],
                'return_reason' => $item['return_reason'] ?? null,
            ]);
        }
    }
}
