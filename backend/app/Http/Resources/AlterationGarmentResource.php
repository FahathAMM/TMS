<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlterationGarmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'garment_type'           => $this->garment_type,
            'description'            => $this->description,
            'tag_number'             => $this->tag_number,
            'quantity'               => $this->quantity,
            'status'                 => $this->status,
            'measurements_required'  => $this->measurements_required,
            'notes'                  => $this->notes,
            'delivered_at'           => $this->delivered_at?->toISOString(),
            'tasks'                  => AlterationTaskResource::collection($this->whenLoaded('tasks')),
            'photos'                 => AlterationGarmentPhotoResource::collection($this->whenLoaded('photos')),
            'measurements'           => AlterationGarmentMeasurementResource::collection($this->whenLoaded('measurements')),
        ];
    }
}
