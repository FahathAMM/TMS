<?php

namespace App\Models\Tailoring;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlterationGarment extends Model
{
    protected $fillable = [
        'alteration_order_id', 'garment_type', 'description', 'tag_number',
        'quantity', 'status', 'measurements_required', 'notes', 'delivered_at',
    ];

    protected $casts = [
        'quantity'               => 'integer',
        'measurements_required'  => 'boolean',
        'delivered_at'           => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (AlterationGarment $garment) {
            if (empty($garment->tag_number)) {
                $order = AlterationOrder::find($garment->alteration_order_id);
                $seq   = static::where('alteration_order_id', $garment->alteration_order_id)->count() + 1;
                $garment->tag_number = ($order?->order_number ?? 'ALT') . '-' . $seq;
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(AlterationOrder::class, 'alteration_order_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(AlterationTask::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(AlterationGarmentPhoto::class);
    }

    public function measurements(): HasMany
    {
        return $this->hasMany(AlterationGarmentMeasurement::class);
    }
}
