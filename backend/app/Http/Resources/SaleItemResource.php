<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SaleItemResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->product_name,
            'product_sku' => $this->product_sku,
            'product' => new ProductResource($this->whenLoaded('product')),
            'quantity' => $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'cost_price' => (float) $this->cost_price,
            'discount' => (float) $this->discount,
            'total' => (float) $this->total,
        ];
    }
}
