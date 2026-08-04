<?php

namespace App\Models;

use App\Enums\AttributeType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attribute extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'type',
        'is_required',
        'is_filterable',
        'sort_order',
    ];

    protected $casts = [
        'type'          => AttributeType::class,
        'is_required'   => 'boolean',
        'is_filterable' => 'boolean',
    ];

    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class)->orderBy('sort_order');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_attributes');
    }

    public function scopeFilterable(Builder $query): Builder
    {
        return $query->where('is_filterable', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
