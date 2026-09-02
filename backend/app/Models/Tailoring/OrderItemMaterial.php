<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItemMaterial extends Model
{
    protected $fillable = ['order_item_id', 'product_id', 'quantity_required', 'status', 'consumed_at'];

    protected $casts = [
        'quantity_required' => 'decimal:2',
        'consumed_at'       => 'datetime',
    ];

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isConsumed(): bool
    {
        return $this->status === 'consumed';
    }
}
