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
            'measurement_field_id'  => $this->measurement_field_id,
            'number'                => $this->measurementField?->number,
            'name'                  => $this->measurementField?->name,
            'unit'                  => $this->measurementField?->unit,
            'measurement_type'      => $this->measurementField?->measurementType ? [
                'id'   => $this->measurementField->measurementType->id,
                'name' => $this->measurementField->measurementType->name,
            ] : null,
            'value'                 => (float) $this->value,
            'recorded_at'           => $this->recorded_at?->toISOString(),
        ];
    }
}
