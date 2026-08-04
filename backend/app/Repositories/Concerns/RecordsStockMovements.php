<?php

namespace App\Repositories\Concerns;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;

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
        ?int    $variantId,
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

        $stockHolder = $variantId
            ? ProductVariant::lockForUpdate()->findOrFail($variantId)
            : Product::lockForUpdate()->findOrFail($productId);

        $before = (float) $stockHolder->stock_quantity;
        $after  = $isInbound ? $before + $quantity : $before - $quantity;

        if ($after < 0) {
            $label = $variantId ? "variant #{$variantId}" : "product #{$productId}";
            throw new \RuntimeException(
                "Insufficient stock for {$label}. Available: {$before}, requested: {$quantity}."
            );
        }

        $movement = StockMovement::create([
            'product_id'      => $productId,
            'variant_id'      => $variantId,
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

        $stockHolder->update(['stock_quantity' => $after]);

        // Keep parent product total in sync when a variant moved
        if ($variantId) {
            $total = ProductVariant::where('product_id', $productId)->sum('stock_quantity');
            Product::where('id', $productId)->update(['stock_quantity' => $total]);
        }

        return $movement;
    }
}
