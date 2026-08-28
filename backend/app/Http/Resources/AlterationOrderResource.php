<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlterationOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'order_number'     => $this->order_number,
            'status'           => $this->status,
            'priority'         => $this->priority,
            'received_date'    => $this->received_date?->toDateString(),
            'promised_date'    => $this->promised_date?->toDateString(),
            'delivered_date'   => $this->delivered_date?->toDateString(),
            'completed_at'     => $this->completed_at?->toISOString(),
            'subtotal'         => (float) $this->subtotal,
            'discount_amount'  => (float) $this->discount_amount,
            'tax_amount'       => (float) $this->tax_amount,
            'total_amount'     => (float) $this->total_amount,
            'paid_amount'      => $this->paid_amount,
            'balance_due'      => $this->balance_due,
            'payment_status'   => $this->payment_status,
            'notes'            => $this->notes,
            'cancel_reason'    => $this->cancel_reason,
            'garment_count'    => $this->whenCounted('garments') ?? $this->whenLoaded('garments', fn () => $this->garments->count()),
            'customer'         => $this->whenLoaded('customer', fn () => [
                'id'     => $this->customer?->id,
                'name'   => $this->customer?->name,
                'mobile' => $this->customer?->mobile,
                'email'  => $this->customer?->email,
            ]),
            'garments'         => AlterationGarmentResource::collection($this->whenLoaded('garments')),
            'payments'         => AlterationOrderPaymentResource::collection($this->whenLoaded('payments')),
            'status_history'   => AlterationStatusHistoryResource::collection($this->whenLoaded('statusHistory')),
            'created_at'       => $this->created_at?->toISOString(),
        ];
    }
}
