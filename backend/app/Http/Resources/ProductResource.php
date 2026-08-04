<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                  => $this->id,
            'uuid'                => $this->uuid,
            'sku'                 => $this->sku,
            'barcode'             => $this->barcode,
            'name'                => $this->name,
            'slug'                => $this->slug,
            'type'                => $this->type?->value,
            'type_label'          => $this->type?->label(),
            'status'              => $this->status?->value,
            'status_label'        => $this->status?->label(),
            'description'         => $this->description,
            'short_description'   => $this->short_description,

            // Pricing
            'cost_price'          => (float) $this->cost_price,
            'selling_price'       => (float) $this->selling_price,
            'compare_price'       => $this->compare_price ? (float) $this->compare_price : null,

            // Inventory
            'stock_quantity'      => $this->stock_quantity,
            'low_stock_threshold' => $this->low_stock_threshold,
            'unit_of_measure'     => $this->unit_of_measure,
            'is_low_stock'        => $this->isLowStock(),

            // Physical
            'image'               => $this->image_url
                ?? ($this->relationLoaded('media') ? $this->media->first()?->file_url : null),
            'weight'              => $this->weight ? (float) $this->weight : null,
            'weight_unit'         => $this->weight_unit,
            'length'              => $this->length ? (float) $this->length : null,
            'width'               => $this->width ? (float) $this->width : null,
            'height'              => $this->height ? (float) $this->height : null,

            // Flags
            'is_active'           => $this->is_active,
            'is_featured'         => $this->is_featured,
            'is_digital'          => $this->is_digital,
            'sort_order'          => $this->sort_order,
            'published_at'        => $this->published_at?->toISOString(),

            // Relations
            'category_id'         => $this->category_id,
            'brand_id'            => $this->brand_id,
            'category'            => new CategoryResource($this->whenLoaded('category')),
            'brand'               => new BrandResource($this->whenLoaded('brand')),
            'tags'                => TagResource::collection($this->whenLoaded('tags')),
            'attributes'          => AttributeResource::collection($this->whenLoaded('attributes')),
            'media'               => ProductMediaResource::collection($this->whenLoaded('media')),
            'seo'                 => new ProductSeoResource($this->whenLoaded('seo')),
            'specifications'      => ProductSpecificationResource::collection($this->whenLoaded('specifications')),
            'variants'            => ProductVariantResource::collection($this->whenLoaded('variants')),

            'created_at'          => $this->created_at->toISOString(),
            'updated_at'          => $this->updated_at->toISOString(),
        ];
    }
}
