<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class StockMovementService
{
    /**
     * Record a stock movement and update the cached stock_quantity in the same transaction.
     * This is the ONLY place stock_quantity should ever change for new modules.
     *
     * @throws \RuntimeException when stock would go negative on an outbound movement
     */
    public function record(
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
        if ($quantity <= 0) {
            throw new \InvalidArgumentException("Stock movement quantity must be positive (got {$quantity}).");
        }

        return DB::transaction(function () use (
            $productId, $variantId, $type, $quantity, $costPrice,
            $referenceType, $referenceId, $notes, $createdBy, $date
        ) {
            $isInbound = in_array($type, StockMovement::INBOUND_TYPES);

            // Lock the target row so concurrent requests don't race
            if ($variantId) {
                $stockHolder = ProductVariant::lockForUpdate()->findOrFail($variantId);
            } else {
                $stockHolder = Product::lockForUpdate()->findOrFail($productId);
            }

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

            // Update the stock holder cache
            $stockHolder->update(['stock_quantity' => $after]);

            // When a variant moves, keep the parent product total in sync
            if ($variantId) {
                $totalStock = ProductVariant::where('product_id', $productId)
                    ->where('is_active', true)
                    ->sum('stock_quantity');

                Product::where('id', $productId)->update(['stock_quantity' => $totalStock]);
            }

            return $movement;
        });
    }

    /**
     * Recalculate a product's stock_quantity by replaying all movements.
     * Use for auditing / reconciliation — not for normal operation.
     */
    public function reconcile(int $productId, ?int $variantId = null): float
    {
        $query = StockMovement::where('product_id', $productId);

        if ($variantId) {
            $query->where('variant_id', $variantId);
        } else {
            $query->whereNull('variant_id');
        }

        $computed = $query->get()->sum(fn ($m) => $m->signed_quantity);

        if ($variantId) {
            ProductVariant::where('id', $variantId)->update(['stock_quantity' => $computed]);
        } else {
            Product::where('id', $productId)->update(['stock_quantity' => $computed]);
        }

        return $computed;
    }
}
