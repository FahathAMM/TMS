<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleReturnItem extends Model
{
    protected $fillable = [
        'sale_return_id', 'product_id', 'variant_id',
        'product_name', 'product_sku',
        'quantity', 'unit_price', 'subtotal', 'return_reason',
    ];

    protected $casts = [
        'quantity'   => 'decimal:3',
        'unit_price' => 'decimal:2',
        'subtotal'   => 'decimal:2',
    ];

    public function saleReturn(): BelongsTo
    {
        return $this->belongsTo(SaleReturn::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id')->withTrashed();
    }
}
