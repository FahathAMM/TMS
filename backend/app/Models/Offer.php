<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Offer extends Model
{
    protected $fillable = ['title', 'type', 'value', 'start_date', 'end_date', 'is_active'];

    protected $casts = [
        'value'      => 'float',
        'start_date' => 'date',
        'end_date'   => 'date',
        'is_active'  => 'boolean',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'offer_products');
    }

    /** Returns true if this offer applies to the given product (global or explicitly listed). */
    public function appliesToProduct(int $productId): bool
    {
        // No products pinned → global offer
        if ($this->products->isEmpty()) {
            return true;
        }

        return $this->products->contains('id', $productId);
    }

    /** Whether the offer is currently valid (active + date window). */
    public function isValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $today = Carbon::today();

        if ($this->start_date && $today->lt($this->start_date)) {
            return false;
        }

        if ($this->end_date && $today->gt($this->end_date)) {
            return false;
        }

        return true;
    }
}
