<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AttributeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'slug'          => $this->slug,
            'type'          => $this->type?->value,
            'type_label'    => $this->type?->label(),
            'is_required'   => $this->is_required,
            'is_filterable' => $this->is_filterable,
            'sort_order'    => $this->sort_order,
            'values'        => AttributeValueResource::collection($this->whenLoaded('values')),
            'created_at'    => $this->created_at->toISOString(),
            'updated_at'    => $this->updated_at->toISOString(),
        ];
    }
}
