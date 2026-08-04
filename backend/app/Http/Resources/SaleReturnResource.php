<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SaleReturnResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'reference_number' => $this->reference_number,
            'return_date'      => $this->return_date?->toDateString(),
            'status'           => $this->status,
            'refund_type'      => $this->refund_type,
            'subtotal'         => (float) $this->subtotal,
            'total_amount'     => (float) $this->total_amount,
            'refund_amount'    => (float) $this->refund_amount,
            'reason'           => $this->reason,
            'notes'            => $this->notes,
            'sale_id'          => $this->sale_id,
            'customer_id'      => $this->customer_id,
            'customer'         => new CustomerResource($this->whenLoaded('customer')),
            'sale'             => new SaleResource($this->whenLoaded('sale')),
            'items'            => SaleReturnItemResource::collection($this->whenLoaded('items')),
            'processed_by'     => $this->processedBy?->name,
            'created_at'       => $this->created_at->toISOString(),
            'updated_at'       => $this->updated_at->toISOString(),
        ];
    }
}
