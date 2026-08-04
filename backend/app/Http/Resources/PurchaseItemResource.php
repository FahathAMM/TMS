<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseItemResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                  => $this->id,
            'purchase_id'         => $this->purchase_id,
            'product_id'          => $this->product_id,
            'variant_id'          => $this->variant_id,
            'product_name'        => $this->product_name,
            'product_sku'         => $this->product_sku,
            'quantity_ordered'    => (float) $this->quantity_ordered,
            'quantity_received'   => (float) $this->quantity_received,
            'remaining_qty'       => (float) $this->quantity_ordered - (float) $this->quantity_received,
            'is_fully_received'   => $this->isFullyReceived(),
            'cost_price'          => (float) $this->cost_price,
            'tax_rate'            => (float) $this->tax_rate,
            'discount_amount'     => (float) $this->discount_amount,
            'subtotal'            => (float) $this->subtotal,
        ];
    }
}
