<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MeasurementField extends Model
{
    protected $fillable = [
        'measurement_type_id', 'number', 'name', 'key', 'unit', 'required', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'required'   => 'boolean',
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
        'number'     => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (MeasurementField $field): void {
            if (empty($field->key)) {
                $field->key = Str::slug($field->name, '_');
            }
        });
    }

    public function measurementType(): BelongsTo
    {
        return $this->belongsTo(MeasurementType::class);
    }
}
