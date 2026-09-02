<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    protected $fillable = [
        'product_id', 'variant_id', 'type',
        'quantity', 'quantity_before', 'quantity_after',
        'cost_price', 'reference_type', 'reference_id',
        'date', 'notes', 'created_by',
    ];

    protected $casts = [
        'quantity'        => 'decimal:3',
        'quantity_before' => 'decimal:3',
        'quantity_after'  => 'decimal:3',
        'cost_price'      => 'decimal:2',
        'date'            => 'date',
    ];

    // Movement types that increase stock
    public const INBOUND_TYPES = [
        'purchase_in',
        'customer_return_in',
        'adjustment_in',
        'opening_stock',
        'material_returned',
    ];

    // Movement types that decrease stock
    public const OUTBOUND_TYPES = [
        'sale_out',
        'supplier_return_out',
        'adjustment_out',
        'material_consumed',
        'waste_scrap',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    // Resolves to Purchase, Sale, SupplierReturn, SaleReturn via morphMap in AppServiceProvider
    public function reference(): MorphTo
    {
        return $this->morphTo('reference');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function isInbound(): bool
    {
        return in_array($this->type, self::INBOUND_TYPES);
    }

    public function isOutbound(): bool
    {
        return in_array($this->type, self::OUTBOUND_TYPES);
    }

    public function getSignedQuantityAttribute(): float
    {
        return $this->isInbound()
            ? (float) $this->quantity
            : -(float) $this->quantity;
    }
}
