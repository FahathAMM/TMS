<?php

namespace App\Models\Tailoring;

use Illuminate\Database\Eloquent\Model;

class GarmentPrice extends Model
{
    protected $fillable = ['garment_type', 'fabric_source', 'price', 'is_active'];

    protected $casts = [
        'price'     => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
