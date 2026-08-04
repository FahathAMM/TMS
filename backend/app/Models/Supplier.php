<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Supplier extends Model
{
    protected $fillable = [
        'name', 'company', 'phone', 'email', 'address', 'city',
        'tax_number', 'opening_balance', 'current_balance', 'status', 'notes',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function contacts(): HasMany
    {
        return $this->hasMany(SupplierContact::class);
    }

    public function primaryContact(): HasOne
    {
        return $this->hasOne(SupplierContact::class)->where('is_primary', true);
    }

    public function ledger(): HasMany
    {
        return $this->hasMany(SupplierLedgerEntry::class)->orderBy('date')->orderBy('id');
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(SupplierReturn::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
