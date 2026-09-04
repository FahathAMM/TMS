<?php

namespace App\Models\Tailoring;

use App\Models\Customers\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    protected $fillable = [
        'customer_id', 'order_number', 'order_type', 'status',
        'subtotal', 'discount_amount', 'tax_amount', 'total_amount',
        'deposit_amount', 'expected_delivery_date', 'is_urgent',
        'payment_method', 'payment_status',
        'notes',
    ];

    protected $casts = [
        'subtotal'         => 'float',
        'discount_amount'  => 'float',
        'tax_amount'       => 'float',
        'total_amount'     => 'float',
        'deposit_amount'   => 'float',
        'expected_delivery_date' => 'date',
        'is_urgent'        => 'boolean',
    ];

    protected $appends = ['paid_amount', 'balance_due'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Order $order) {
            if (empty($order->order_number)) {
                $order->order_number = static::generateOrderNumber();
            }
        });
    }

    /**
     * ORD-YYYYMMDD-NNNN, with the sequence resetting to 0001 each day.
     * Locks the day's rows for the duration of the surrounding transaction
     * so concurrent creates on the same day don't collide.
     */
    public static function generateOrderNumber(): string
    {
        $prefix = 'ORD-' . now()->format('Ymd') . '-';

        return DB::transaction(function () use ($prefix) {
            $lastNumber = static::where('order_number', 'like', $prefix . '%')
                ->lockForUpdate()
                ->orderByDesc('order_number')
                ->value('order_number');

            $nextSequence = $lastNumber ? ((int) substr($lastNumber, strlen($prefix))) + 1 : 1;

            return $prefix . str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);
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
