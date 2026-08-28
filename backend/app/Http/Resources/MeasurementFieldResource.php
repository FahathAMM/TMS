<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MeasurementFieldResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'measurement_type_id'   => $this->measurement_type_id,
            'number'                => $this->number,
            'name'                  => $this->name,
            'key'                   => $this->key,
            'unit'                  => $this->unit,
            'required'              => $this->required,
            'sort_order'            => $this->sort_order,
            'is_active'             => $this->is_active,
        ];
    }
}
