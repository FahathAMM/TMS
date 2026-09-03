<?php

namespace App\Models\Inventory;

use App\Models\Administration\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierLedgerEntry extends Model
{
    protected $fillable = [
        'supplier_id', 'type', 'amount', 'balance_after',
        'reference_type', 'reference_id', 'date', 'description', 'created_by',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'balance_after' => 'decimal:2',
        'date'         => 'date',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function isCredit(): bool
    {
        return $this->type === 'credit';
    }

    public function isDebit(): bool
    {
        return $this->type === 'debit';
    }
}
