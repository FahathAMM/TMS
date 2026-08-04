<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'invoice_no' => $this->invoice_no,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'user' => new UserResource($this->whenLoaded('user')),
            'customer_id' => $this->customer_id,
            'items' => SaleItemResource::collection($this->whenLoaded('items')),
            'subtotal' => (float) $this->subtotal,
            'discount_amount' => (float) $this->discount_amount,
            'tax_amount' => (float) $this->tax_amount,
            'total_amount' => (float) $this->total_amount,
            'paid_amount' => (float) $this->paid_amount,
            'change_amount' => (float) $this->change_amount,
            'payment_method' => $this->payment_method,
            'status' => $this->status,
            'notes' => $this->notes,
            'sold_at' => $this->sold_at->toISOString(),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
