<?php

namespace App\Models;

use App\Enums\MediaType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductMedia extends Model
{
    protected $table = 'product_media';

    protected $fillable = [
        'product_id',
        'variant_id',
        'type',
        'file_name',
        'file_path',
        'file_url',
        'thumbnail_url',
        'mime_type',
        'file_size',
        'alt',
        'title',
        'sort_order',
        'is_primary',
    ];

    protected $casts = [
        'type'       => MediaType::class,
        'is_primary' => 'boolean',
        'file_size'  => 'integer',
        'sort_order' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function scopeImages(Builder $query): Builder
    {
        return $query->where('type', MediaType::Image->value);
    }

    public function scopeVideos(Builder $query): Builder
    {
        return $query->where('type', MediaType::Video->value);
    }

    public function scopePrimary(Builder $query): Builder
    {
        return $query->where('is_primary', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
