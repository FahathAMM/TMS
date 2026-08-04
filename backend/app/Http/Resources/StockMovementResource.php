<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
{
    private static array $TYPE_LABELS = [
        'purchase_in'          => 'Purchase In',
        'sale_out'             => 'Sale Out',
        'supplier_return_out'  => 'Supplier Return',
        'customer_return_in'   => 'Customer Return',
        'adjustment_in'        => 'Adjustment (+)',
        'adjustment_out'       => 'Adjustment (-)',
        'opening_stock'        => 'Opening Stock',
        'material_consumed'    => 'Material Consumed',
        'material_returned'    => 'Material Returned',
        'waste_scrap'          => 'Waste / Scrap',
    ];

    public function toArray($request): array
    {
        return [
            'id'              => $this->id,
            'product_id'      => $this->product_id,
            'variant_id'      => $this->variant_id,
            'type'            => $this->type,
            'type_label'      => self::$TYPE_LABELS[$this->type] ?? $this->type,
            'is_inbound'      => $this->isInbound(),
            'quantity'        => (float) $this->quantity,
            'quantity_before' => (float) $this->quantity_before,
            'quantity_after'  => (float) $this->quantity_after,
            'cost_price'      => $this->cost_price ? (float) $this->cost_price : null,
            'reference_type'  => $this->reference_type,
            'reference_id'    => $this->reference_id,
            'date'            => $this->date?->toDateString(),
            'notes'           => $this->notes,
            'product'         => new ProductResource($this->whenLoaded('product')),
            'created_by'      => $this->createdBy?->name,
            'created_at'      => $this->created_at->toISOString(),
        ];
    }
}
