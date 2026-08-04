<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SupplierReturnItemResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                 => $this->id,
            'supplier_return_id' => $this->supplier_return_id,
            'product_id'         => $this->product_id,
            'variant_id'         => $this->variant_id,
            'product_name'       => $this->product_name,
            'product_sku'        => $this->product_sku,
            'quantity'           => (float) $this->quantity,
            'cost_price'         => (float) $this->cost_price,
            'subtotal'           => (float) $this->subtotal,
        ];
    }
}
