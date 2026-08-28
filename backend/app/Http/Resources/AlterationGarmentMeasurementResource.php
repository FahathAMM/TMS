<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlterationGarmentMeasurementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'measurement_field_id'  => $this->measurement_field_id,
            'number'                => $this->whenLoaded('measurementField', fn () => $this->measurementField?->number),
            'name'                  => $this->whenLoaded('measurementField', fn () => $this->measurementField?->name),
            'unit'                  => $this->whenLoaded('measurementField', fn () => $this->measurementField?->unit),
            'current_value'         => $this->current_value,
            'target_value'          => $this->target_value,
        ];
    }
}
