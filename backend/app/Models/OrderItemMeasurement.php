<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItemMeasurement extends Model
{
    protected $fillable = ['order_item_id', 'measurement_field_id', 'value'];

    protected $casts = [
        'value' => 'decimal:2',
    ];

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function measurementField(): BelongsTo
    {
        return $this->belongsTo(MeasurementField::class);
    }
}
