<?php

namespace App\Models\Tailoring;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlterationGarmentMeasurement extends Model
{
    protected $fillable = ['alteration_garment_id', 'measurement_field_id', 'current_value', 'target_value'];

    protected $casts = [
        'current_value' => 'float',
        'target_value'  => 'float',
    ];

    public function garment(): BelongsTo
    {
        return $this->belongsTo(AlterationGarment::class, 'alteration_garment_id');
    }

    public function measurementField(): BelongsTo
    {
        return $this->belongsTo(MeasurementField::class);
    }
}
