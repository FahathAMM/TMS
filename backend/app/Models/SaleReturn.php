<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class SaleReturn extends Model
{
    protected $fillable = [
        'sale_id', 'customer_id', 'reference_number', 'return_date',
        'status', 'refund_type', 'subtotal', 'total_amount',
        'refund_amount', 'reason', 'notes', 'processed_by',
    ];

    protected $casts = [
        'return_date'   => 'date',
        'subtotal'      => 'decimal:2',
        'total_amount'  => 'decimal:2',
        'refund_amount' => 'decimal:2',
    ];

    // ── Boot ───────────────────────────────────────────────────────────────────

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->reference_number)) {
                $model->reference_number = static::generateReference();
            }
        });
    }

    public static function generateReference(): string
    {
        $year  = now()->format('Y');
        $count = static::whereYear('created_at', $year)->count() + 1;
        return 'CRN-' . $year . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    // ── Relationships ──────────────────────────────────────────────────────────

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class)->withDefault();
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withDefault();
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class);
    }

    public function stockMovements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
}
