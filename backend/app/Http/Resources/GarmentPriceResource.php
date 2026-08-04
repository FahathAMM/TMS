<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GarmentPriceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'garment_type'  => $this->garment_type,
            'fabric_source' => $this->fabric_source,
            'price'         => (float) $this->price,
            'is_active'     => $this->is_active,
            'created_at'    => $this->created_at?->toISOString(),
        ];
    }
}
