<?php

namespace App\Repositories\Concerns;

use App\Models\Inventory\Product;
use App\Models\Inventory\StockMovement;

trait RecordsStockMovements
{
    /**
     * Record a stock movement and update cached stock_quantity in the same transaction.
     * Must be called inside an existing DB::transaction().
     *
     * @throws \RuntimeException when an outbound movement would push stock below zero
     */
    protected function recordMovement(
        int     $productId,
        string  $type,
        float   $quantity,
        ?float  $costPrice     = null,
        ?string $referenceType = null,
        ?int    $referenceId   = null,
        ?string $notes         = null,
        ?int    $createdBy     = null,
        ?string $date          = null
    ): StockMovement {
        $isInbound = in_array($type, StockMovement::INBOUND_TYPES);

        $product = Product::lockForUpdate()->findOrFail($productId);

        $before = (float) $product->stock_quantity;
        $after  = $isInbound ? $before + $quantity : $before - $quantity;

        if ($after < 0) {
            throw new \RuntimeException(
                "Insufficient stock for product #{$productId}. Available: {$before}, requested: {$quantity}."
            );
        }

        $movement = StockMovement::create([
            'product_id'      => $productId,
            'type'            => $type,
            'quantity'        => $quantity,
            'quantity_before' => $before,
            'quantity_after'  => $after,
            'cost_price'      => $costPrice,
            'reference_type'  => $referenceType,
            'reference_id'    => $referenceId,
            'date'            => $date ?? now()->toDateString(),
            'notes'           => $notes,
            'created_by'      => $createdBy,
        ]);

        $product->update(['stock_quantity' => $after]);

        return $movement;
    }
}
