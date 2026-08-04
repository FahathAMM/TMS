<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'customer_id', 'order_number', 'order_type', 'status',
        'subtotal', 'discount_amount', 'tax_amount', 'shipping_amount', 'total_amount',
        'deposit_amount', 'expected_delivery_date',
        'payment_method', 'payment_status',
        'shipping_name', 'shipping_phone', 'shipping_email',
        'shipping_address', 'shipping_city', 'shipping_zip',
        'notes',
    ];

    protected $casts = [
        'subtotal'         => 'float',
        'discount_amount'  => 'float',
        'tax_amount'       => 'float',
        'shipping_amount'  => 'float',
        'total_amount'     => 'float',
        'deposit_amount'   => 'float',
        'expected_delivery_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Order $order) {
            if (empty($order->order_number)) {
                $order->order_number = 'ORD-' . strtoupper(uniqid());
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(OrderPayment::class);
    }

    public function getPaidAmountAttribute(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    public function getBalanceDueAttribute(): float
    {
        return round((float) $this->total_amount - $this->paid_amount, 2);
    }

    public function isFullyPaid(): bool
    {
        return $this->balance_due <= 0;
    }
}
