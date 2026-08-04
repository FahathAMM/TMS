<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderPaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'order_id'               => $this->order_id,
            'amount'                 => (float) $this->amount,
            'payment_method'         => $this->payment_method,
            'payment_type'           => $this->payment_type,
            'transaction_reference'  => $this->transaction_reference,
            'paid_at'                => $this->paid_at?->toISOString(),
            'created_by'             => $this->whenLoaded('createdBy', fn () => $this->createdBy?->name),
        ];
    }
}
