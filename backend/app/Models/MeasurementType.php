<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MeasurementType extends Model
{
    protected $fillable = ['name', 'category', 'unit'];

    public function customerMeasurements(): HasMany
    {
        return $this->hasMany(CustomerMeasurement::class);
    }
}
