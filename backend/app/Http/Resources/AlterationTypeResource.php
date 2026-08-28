<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlterationTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'price'      => (float) $this->price,
            'is_active'  => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
