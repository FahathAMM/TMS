<?php

namespace App\Models\Tailoring;

use App\Models\Administration\User;
use App\Models\Customers\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AlterationOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_number', 'customer_id', 'status', 'priority',
        'received_date', 'promised_date', 'delivered_date', 'completed_at',
        'subtotal', 'discount_amount', 'tax_amount', 'total_amount',
        'payment_status', 'notes', 'received_by', 'created_by',
        'cancelled_at', 'cancel_reason',
    ];

    protected $casts = [
        'received_date'    => 'date',
        'promised_date'    => 'date',
        'delivered_date'   => 'date',
        'subtotal'         => 'float',
        'discount_amount'  => 'float',
        'tax_amount'       => 'float',
        'total_amount'     => 'float',
        'cancelled_at'     => 'datetime',
        'completed_at'     => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (AlterationOrder $order) {
            if (empty($order->order_number)) {
                $order->order_number = 'ALT-' . strtoupper(uniqid());
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function garments(): HasMany
    {
        return $this->hasMany(AlterationGarment::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(AlterationOrderPayment::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(AlterationStatusHistory::class)->latest();
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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
