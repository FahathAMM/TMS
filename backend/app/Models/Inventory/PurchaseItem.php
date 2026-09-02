<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends Model
{
    protected $fillable = [
        'purchase_id', 'product_id', 'variant_id',
        'product_name', 'product_sku',
        'quantity_ordered', 'quantity_received',
        'cost_price', 'tax_rate', 'discount_amount', 'subtotal',
    ];

    protected $casts = [
        'quantity_ordered'  => 'decimal:3',
        'quantity_received' => 'decimal:3',
        'cost_price'        => 'decimal:2',
        'tax_rate'          => 'decimal:2',
        'discount_amount'   => 'decimal:2',
        'subtotal'          => 'decimal:2',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id')->withTrashed();
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function getRemainingQtyAttribute(): float
    {
        return (float) $this->quantity_ordered - (float) $this->quantity_received;
    }

    public function isFullyReceived(): bool
    {
        return (float) $this->quantity_received >= (float) $this->quantity_ordered;
    }
}
