<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierReturnItem extends Model
{
    protected $fillable = [
        'supplier_return_id', 'product_id', 'variant_id',
        'product_name', 'product_sku',
        'quantity', 'cost_price', 'subtotal',
    ];

    protected $casts = [
        'quantity'   => 'decimal:3',
        'cost_price' => 'decimal:2',
        'subtotal'   => 'decimal:2',
    ];

    public function supplierReturn(): BelongsTo
    {
        return $this->belongsTo(SupplierReturn::class);
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
