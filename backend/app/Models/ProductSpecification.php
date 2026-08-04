<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSpecification extends Model
{
    protected $fillable = [
        'product_id',
        'group',
        'label',
        'value',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('group')->orderBy('sort_order')->orderBy('label');
    }

    public function scopeInGroup(Builder $query, string $group): Builder
    {
        return $query->where('group', $group);
    }
}
