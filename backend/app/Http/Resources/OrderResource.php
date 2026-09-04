<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                       => $this->id,
            'order_number'             => $this->order_number,
            'order_type'               => $this->order_type,
            'status'                   => $this->status,
            'payment_method'           => $this->payment_method,
            'payment_status'           => $this->payment_status,
            'subtotal'                 => (float) $this->subtotal,
            'discount_amount'          => (float) $this->discount_amount,
            'tax_amount'               => (float) $this->tax_amount,
            'total_amount'             => (float) $this->total_amount,
            'deposit_amount'           => (float) $this->deposit_amount,
            'paid_amount'              => $this->paid_amount,
            'balance_due'              => $this->balance_due,
            'expected_delivery_date'   => $this->expected_delivery_date?->toDateString(),
            'is_urgent'                => (bool) $this->is_urgent,
            'notes'                    => $this->notes,
            'customer'                 => $this->whenLoaded('customer', fn () => [
                'id'      => $this->customer?->id,
                'name'    => $this->customer?->name,
                'mobile'  => $this->customer?->mobile,
                'email'   => $this->customer?->email,
            ]),
            'items'                    => OrderItemResource::collection($this->whenLoaded('items')),
            'payments'                 => OrderPaymentResource::collection($this->whenLoaded('payments')),
            'created_at'               => $this->created_at?->toISOString(),
            'updated_at'               => $this->updated_at?->toISOString(),
        ];
    }
}
