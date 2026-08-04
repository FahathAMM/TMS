<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AttributeValueResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'           => $this->id,
            'attribute_id' => $this->attribute_id,
            'value'        => $this->value,
            'label'        => $this->label,
            'color_code'   => $this->color_code,
            'sort_order'   => $this->sort_order,
            'attribute'    => new AttributeResource($this->whenLoaded('attribute')),
        ];
    }
}
