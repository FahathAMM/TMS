<?php

namespace App\Repositories;

use App\Models\Product;
use App\Models\StockMovement;
use App\Repositories\Concerns\RecordsStockMovements;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class StockMovementRepo
{
    use RecordsStockMovements;

    // ─── Listing ──────────────────────────────────────────────────────────────

    public function getData(Request $request): LengthAwarePaginator
    {
        $query = StockMovement::with(['product', 'createdBy']);

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('variant_id')) {
            $query->where('variant_id', $request->variant_id);
        }

        if ($request->filled('type')) {
            $query->whereIn('type', (array) $request->type);
        }

        if ($request->filled('reference_type')) {
            $query->where('reference_type', $request->reference_type);
        }

        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        return $query->latest('date')->latest('id')->paginate($request->input('per_page', 20));
    }

    public function getForProduct(Product $product, Request $request): LengthAwarePaginator
    {
        $query = StockMovement::with('createdBy')
            ->where('product_id', $product->id);

        if ($request->filled('variant_id')) {
            $query->where('variant_id', $request->variant_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        return $query->latest('date')->latest('id')->paginate($request->input('per_page', 20));
    }

    // ─── Manual Adjustment ────────────────────────────────────────────────────

    public function adjust(array $data, int $userId): StockMovement
    {
        $type = $data['type']; // 'adjustment_in' | 'adjustment_out'

        return \Illuminate\Support\Facades\DB::transaction(
            fn() => $this->recordMovement(
                productId:     (int) $data['product_id'],
                variantId:     isset($data['variant_id']) ? (int) $data['variant_id'] : null,
                type:          $type,
                quantity:      (float) $data['quantity'],
                referenceType: 'adjustment',
                referenceId:   null,
                notes:         $data['notes'] ?? null,
                createdBy:     $userId,
                date:          $data['date'] ?? now()->toDateString(),
            )
        );
    }
}
