<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerMeasurementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'measurement_type_id'   => $this->measurement_type_id,
            'name'                  => $this->measurementType?->name,
            'category'              => $this->measurementType?->category,
            'unit'                  => $this->measurementType?->unit,
            'value'                 => (float) $this->value,
            'recorded_at'           => $this->recorded_at?->toISOString(),
        ];
    }
}
