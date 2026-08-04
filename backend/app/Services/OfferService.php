<?php

namespace App\Services;

use App\Models\Offer;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class OfferService
{
    /**
     * All currently active offers (within date window), eager-loaded with products.
     */
    public function getActiveOffers(): Collection
    {
        $today = Carbon::today();

        return Offer::with('products')
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('start_date')->orWhereDate('start_date', '<=', $today))
            ->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', $today))
            ->get();
    }

    /**
     * Pick the best (highest-saving) offer for a single product from an already-fetched list.
     */
    public function getBestOfferForProduct(int $productId, float $unitPrice, Collection $activeOffers): ?Offer
    {
        return $activeOffers
            ->filter(fn (Offer $o) => $o->appliesToProduct($productId))
            ->sortByDesc(fn (Offer $o) => $this->calculateUnitDiscount($unitPrice, $o))
            ->first();
    }

    /**
     * Compute the discount amount for a single unit of a product.
     */
    public function calculateUnitDiscount(float $unitPrice, Offer $offer): float
    {
        if ($offer->type === 'percentage') {
            return round($unitPrice * $offer->value / 100, 2);
        }

        // fixed: cap at unit price so we never produce negative totals
        return min($offer->value, $unitPrice);
    }

    /**
     * Compute the total line discount (all units) for a cart item.
     */
    public function calculateLineDiscount(float $unitPrice, int $quantity, Offer $offer): float
    {
        return $this->calculateUnitDiscount($unitPrice, $offer) * $quantity;
    }

    /**
     * Apply the best active offer to each cart item.
     *
     * Each item must have: product_id, unit_price, quantity.
     * Returns the same array with 'discount' set to the computed line discount.
     */
    public function applyToCart(array $items, Collection $activeOffers): array
    {
        return array_map(function (array $item) use ($activeOffers) {
            $offer = $this->getBestOfferForProduct(
                $item['product_id'],
                (float) $item['unit_price'],
                $activeOffers
            );

            if ($offer) {
                $item['discount'] = $this->calculateLineDiscount(
                    (float) $item['unit_price'],
                    (int)   $item['quantity'],
                    $offer
                );
            } else {
                $item['discount'] = $item['discount'] ?? 0;
            }

            return $item;
        }, $items);
    }
}
