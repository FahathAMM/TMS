<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MeasurementTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'description' => $this->description,
            'image_url'   => $this->image_url,
            'is_active'   => $this->is_active,
            'fields'      => MeasurementFieldResource::collection(
                $this->whenLoaded('fields', fn () => $this->fields->sortBy('sort_order')->values())
            ),
            'created_at'  => $this->created_at?->toISOString(),
        ];
    }
}
