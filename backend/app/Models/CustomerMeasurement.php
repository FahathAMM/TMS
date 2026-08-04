<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerMeasurement extends Model
{
    protected $fillable = ['customer_id', 'measurement_type_id', 'value', 'recorded_at'];

    protected $casts = [
        'value'       => 'decimal:2',
        'recorded_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function measurementType(): BelongsTo
    {
        return $this->belongsTo(MeasurementType::class);
    }
}
