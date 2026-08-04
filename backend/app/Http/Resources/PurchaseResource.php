<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'reference_number' => $this->reference_number,
            'purchase_date'    => $this->purchase_date?->toDateString(),
            'expected_delivery'=> $this->expected_delivery?->toDateString(),
            'received_date'    => $this->received_date?->toDateString(),
            'status'           => $this->status,
            'payment_status'   => $this->payment_status,
            'subtotal'         => (float) $this->subtotal,
            'tax_amount'       => (float) $this->tax_amount,
            'discount_amount'  => (float) $this->discount_amount,
            'shipping_cost'    => (float) $this->shipping_cost,
            'total_amount'     => (float) $this->total_amount,
            'paid_amount'      => (float) $this->paid_amount,
            'due_amount'       => (float) $this->due_amount,
            'notes'            => $this->notes,
            'supplier_id'      => $this->supplier_id,
            'supplier'         => new SupplierResource($this->whenLoaded('supplier')),
            'items'            => PurchaseItemResource::collection($this->whenLoaded('items')),
            'payments'         => PurchasePaymentResource::collection($this->whenLoaded('payments')),
            'created_by'       => $this->createdBy?->name,
            'created_at'       => $this->created_at->toISOString(),
            'updated_at'       => $this->updated_at->toISOString(),
        ];
    }
}
