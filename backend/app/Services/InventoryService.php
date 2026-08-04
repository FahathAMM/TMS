<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockAdjustment;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function getAll(array $filters = [])
    {
        $query = Product::with(['category', 'brand'])->select('products.*');

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('sku', 'like', "%{$filters['search']}%");
            });
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    public function adjust(array $data, int $userId): StockAdjustment
    {
        return DB::transaction(function () use ($data, $userId) {
            $product = Product::lockForUpdate()->findOrFail($data['product_id']);
            $before = $product->stock_quantity;
            $change = $data['type'] === 'addition' ? abs($data['quantity']) : -abs($data['quantity']);
            $after = $before + $change;

            if ($after < 0) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'quantity' => ['Stock cannot go below zero.'],
                ]);
            }

            $product->update(['stock_quantity' => $after]);

            return StockAdjustment::create([
                'product_id' => $product->id,
                'user_id' => $userId,
                'type' => $data['type'],
                'quantity_before' => $before,
                'quantity_changed' => $change,
                'quantity_after' => $after,
                'reason' => $data['reason'] ?? null,
            ]);
        });
    }

    public function getHistory(array $filters = [])
    {
        $query = StockAdjustment::with(['product', 'user', 'sale'])->latest();

        if (!empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function getLowStock()
    {
        return Product::with(['category', 'brand'])
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->where('is_active', true)
            ->get();
    }
}
