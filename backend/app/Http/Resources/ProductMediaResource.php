<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductMediaResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'product_id'    => $this->product_id,
            'variant_id'    => $this->variant_id,
            'type'          => $this->type?->value,
            'file_name'     => $this->file_name,
            'file_url'      => $this->file_url,
            'thumbnail_url' => $this->thumbnail_url,
            'mime_type'     => $this->mime_type,
            'file_size'     => $this->file_size,
            'alt'           => $this->alt,
            'title'         => $this->title,
            'sort_order'    => $this->sort_order,
            'is_primary'    => $this->is_primary,
            'created_at'    => $this->created_at->toISOString(),
        ];
    }
}
