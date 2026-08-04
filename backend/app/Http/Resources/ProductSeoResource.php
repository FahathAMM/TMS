<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductSeoResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'product_id'       => $this->product_id,
            'meta_title'       => $this->meta_title,
            'meta_description' => $this->meta_description,
            'meta_keywords'    => $this->meta_keywords,
            'og_title'         => $this->og_title,
            'og_description'   => $this->og_description,
            'og_image'         => $this->og_image,
            'canonical_url'    => $this->canonical_url,
            'schema_markup'    => $this->schema_markup,
        ];
    }
}
