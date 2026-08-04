<?php

namespace App\Repositories;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SupplierReturn;
use App\Repositories\Concerns\RecordsStockMovements;
use App\Repositories\Concerns\UpdatesSupplierLedger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierReturnRepo
{
    use RecordsStockMovements, UpdatesSupplierLedger;

    // ─── Listing ──────────────────────────────────────────────────────────────

    public function getData(Request $request): LengthAwarePaginator
    {
        $query = SupplierReturn::with(['supplier', 'purchase']);

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

        if ($request->filled('date_from')) {
            $query->where('return_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('return_date', '<=', $request->date_to);
        }

        return $query->latest('return_date')->paginate($request->input('per_page', 15));
    }

    public function findWithDetails(int $id): SupplierReturn
    {
        return SupplierReturn::with(['supplier', 'purchase', 'items', 'createdBy'])
            ->findOrFail($id);
    }

    // ─── CRUD ─────────────────────────────────────────────────────────────────

    public function store(array $data, int $userId): SupplierReturn
    {
        return DB::transaction(function () use ($data, $userId): SupplierReturn {
            $items = $data['items'];
            unset($data['items']);

            $data['created_by'] = $userId;
            $data['status']     = 'draft';

            $subtotal = collect($items)->sum(fn($i) => (float) $i['cost_price'] * (float) $i['quantity']);

            $return = SupplierReturn::create(array_merge($data, [
                'subtotal'     => $subtotal,
                'total_amount' => $subtotal,
            ]));

            $this->createItems($return, $items);

            return $this->findWithDetails($return->id);
        });
    }

    public function update(SupplierReturn $return, array $data): SupplierReturn
    {
        if ($return->status !== 'draft') {
            abort(422, 'Only draft returns can be edited.');
        }

        return DB::transaction(function () use ($return, $data): SupplierReturn {
            if (isset($data['items'])) {
                $return->items()->delete();
                $subtotal = collect($data['items'])->sum(fn($i) => (float) $i['cost_price'] * (float) $i['quantity']);
                $data['subtotal']     = $subtotal;
                $data['total_amount'] = $subtotal;
                $this->createItems($return, $data['items']);
                unset($data['items']);
            }

            $return->update($data);

            return $this->findWithDetails($return->id);
        });
    }

    public function destroy(SupplierReturn $return): void
    {
        if ($return->status !== 'draft') {
            abort(422, 'Only draft returns can be deleted.');
        }

        DB::transaction(function () use ($return): void {
            $return->items()->delete();
            $return->delete();
        });
    }

    // ─── Confirm ──────────────────────────────────────────────────────────────

    public function confirm(SupplierReturn $return, int $userId): SupplierReturn
    {
        if (!$return->canBeConfirmed()) {
            abort(422, "Return {$return->reference_number} cannot be confirmed (status: {$return->status}).");
        }

        DB::transaction(function () use ($return, $userId): void {
            $items = $return->items()->get();

            foreach ($items as $item) {
                $this->recordMovement(
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

            $this->debitSupplier(
                supplier:      $return->supplier,
                amount:        (float) $return->total_amount,
                referenceType: 'supplier_return',
                referenceId:   $return->id,
                description:   "Supplier return: {$return->reference_number}",
                createdBy:     $userId,
                date:          $return->return_date->toDateString(),
            );
        });

        return $this->findWithDetails($return->id);
    }

    // ─── Private ──────────────────────────────────────────────────────────────

    private function createItems(SupplierReturn $return, array $items): void
    {
        foreach ($items as $item) {
            $product   = Product::find($item['product_id']);
            $variantId = $item['variant_id'] ?? null;
            $sku       = $variantId ? ProductVariant::find($variantId)?->sku : $product?->sku;

            $return->items()->create([
                'product_id'   => $item['product_id'],
                'variant_id'   => $variantId,
                'product_name' => $product?->name ?? 'Unknown',
                'product_sku'  => $sku,
                'quantity'     => $item['quantity'],
                'cost_price'   => $item['cost_price'],
                'subtotal'     => (float) $item['cost_price'] * (float) $item['quantity'],
            ]);
        }
    }
}
