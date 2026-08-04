<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class SupplierReturn extends Model
{
    protected $fillable = [
        'supplier_id', 'purchase_id', 'reference_number', 'return_date',
        'status', 'subtotal', 'total_amount', 'reason', 'notes', 'created_by',
    ];

    protected $casts = [
        'return_date'  => 'date',
        'subtotal'     => 'decimal:2',
        'total_amount' => 'decimal:2',
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
        return 'SRN-' . $year . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    // ── Relationships ──────────────────────────────────────────────────────────

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class)->withDefault();
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupplierReturnItem::class);
    }

    public function stockMovements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function canBeConfirmed(): bool
    {
        return $this->status === 'draft' && $this->items()->exists();
    }
}
