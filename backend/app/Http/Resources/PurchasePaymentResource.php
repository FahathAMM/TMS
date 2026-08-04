<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PurchasePaymentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'purchase_id'    => $this->purchase_id,
            'amount'         => (float) $this->amount,
            'payment_method' => $this->payment_method,
            'payment_date'   => $this->payment_date?->toDateString(),
            'reference'      => $this->reference,
            'notes'          => $this->notes,
            'created_by'     => $this->createdBy?->name,
            'created_at'     => $this->created_at->toISOString(),
        ];
    }
}
