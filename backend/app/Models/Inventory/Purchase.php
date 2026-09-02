<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Purchase extends Model
{
    protected $fillable = [
        'supplier_id', 'reference_number', 'purchase_date', 'expected_delivery',
        'received_date', 'status', 'payment_status', 'subtotal', 'tax_amount',
        'discount_amount', 'shipping_cost', 'total_amount', 'paid_amount',
        'due_amount', 'notes', 'created_by',
    ];

    protected $casts = [
        'purchase_date'     => 'date',
        'expected_delivery' => 'date',
        'received_date'     => 'date',
        'subtotal'          => 'decimal:2',
        'tax_amount'        => 'decimal:2',
        'discount_amount'   => 'decimal:2',
        'shipping_cost'     => 'decimal:2',
        'total_amount'      => 'decimal:2',
        'paid_amount'       => 'decimal:2',
        'due_amount'        => 'decimal:2',
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
        return 'PO-' . $year . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    // ── Relationships ──────────────────────────────────────────────────────────

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PurchasePayment::class)->orderBy('payment_date');
    }

    public function supplierReturns(): HasMany
    {
        return $this->hasMany(SupplierReturn::class);
    }

    public function stockMovements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeUnpaid($query)
    {
        return $query->whereIn('payment_status', ['unpaid', 'partial']);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['draft', 'ordered', 'partial']);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function isFullyReceived(): bool
    {
        return $this->status === 'received';
    }

    public function recalculateTotals(): void
    {
        $subtotal = $this->items()->sum(\DB::raw('cost_price * quantity_ordered'));
        $this->update([
            'subtotal'     => $subtotal,
            'total_amount' => $subtotal + $this->tax_amount + $this->shipping_cost - $this->discount_amount,
            'due_amount'   => ($subtotal + $this->tax_amount + $this->shipping_cost - $this->discount_amount) - $this->paid_amount,
        ]);
    }
}
