<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductSpecificationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'product_id' => $this->product_id,
            'group'      => $this->group,
            'label'      => $this->label,
            'value'      => $this->value,
            'sort_order' => $this->sort_order,
        ];
    }
}
