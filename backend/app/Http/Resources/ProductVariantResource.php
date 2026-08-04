<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                  => $this->id,
            'product_id'          => $this->product_id,
            'sku'                 => $this->sku,
            'price'               => (float) $this->price,
            'compare_price'       => $this->compare_price ? (float) $this->compare_price : null,
            'cost_price'          => $this->cost_price ? (float) $this->cost_price : null,
            'stock_quantity'      => $this->stock_quantity,
            'low_stock_threshold' => $this->low_stock_threshold,
            'is_low_stock'        => $this->is_low_stock,
            'weight'              => $this->weight ? (float) $this->weight : null,
            'image'               => $this->image,
            'is_active'           => $this->is_active,
            'sort_order'          => $this->sort_order,
            'attribute_values'    => AttributeValueResource::collection($this->whenLoaded('attributeValues')),
            'created_at'          => $this->created_at->toISOString(),
            'updated_at'          => $this->updated_at->toISOString(),
        ];
    }
}
