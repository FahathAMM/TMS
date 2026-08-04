<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SaleReturnItemResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'sale_return_id' => $this->sale_return_id,
            'product_id'     => $this->product_id,
            'variant_id'     => $this->variant_id,
            'product_name'   => $this->product_name,
            'product_sku'    => $this->product_sku,
            'quantity'       => (float) $this->quantity,
            'unit_price'     => (float) $this->unit_price,
            'subtotal'       => (float) $this->subtotal,
            'return_reason'  => $this->return_reason,
        ];
    }
}
