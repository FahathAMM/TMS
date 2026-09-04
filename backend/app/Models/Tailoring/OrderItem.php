<?php

namespace App\Models\Tailoring;

use App\Models\Inventory\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'product_id',
        'product_name', 'product_sku', 'product_image',
        'garment_type', 'measurement_type_id', 'fabric_source', 'style_specifications',
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


    public function materials(): HasMany
    {
        return $this->hasMany(OrderItemMaterial::class);
    }

    public function measurementType(): BelongsTo
    {
        return $this->belongsTo(MeasurementType::class);
    }

    public function measurements(): HasMany
    {
        return $this->hasMany(OrderItemMeasurement::class);
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
