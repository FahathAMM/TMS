<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SupplierReturnResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'reference_number' => $this->reference_number,
            'return_date'      => $this->return_date?->toDateString(),
            'status'           => $this->status,
            'subtotal'         => (float) $this->subtotal,
            'total_amount'     => (float) $this->total_amount,
            'reason'           => $this->reason,
            'notes'            => $this->notes,
            'supplier_id'      => $this->supplier_id,
            'purchase_id'      => $this->purchase_id,
            'supplier'         => new SupplierResource($this->whenLoaded('supplier')),
            'purchase'         => new PurchaseResource($this->whenLoaded('purchase')),
            'items'            => SupplierReturnItemResource::collection($this->whenLoaded('items')),
            'created_by'       => $this->createdBy?->name,
            'created_at'       => $this->created_at->toISOString(),
            'updated_at'       => $this->updated_at->toISOString(),
        ];
    }
}
