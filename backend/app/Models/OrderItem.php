<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'product_id', 'variant_id',
        'product_name', 'product_sku', 'product_image',
        'garment_type', 'fabric_source', 'style_specifications',
        'production_status', 'job_card_number', 'qc_notes', 'qc_passed_at',
        'unit_price', 'quantity', 'discount', 'total',
    ];

    protected $casts = [
        'unit_price'            => 'float',
        'discount'              => 'float',
        'total'                 => 'float',
        'style_specifications'  => 'array',
        'qc_passed_at'          => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (OrderItem $item): void {
            if (empty($item->job_card_number)) {
                $item->job_card_number = 'JC-' . strtoupper(Str::random(8));
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(OrderItemMaterial::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TailorAssignment::class);
    }

    public function currentTailor(): ?Tailor
    {
        return $this->assignments()->latest('assigned_at')->first()?->tailor;
    }
}
