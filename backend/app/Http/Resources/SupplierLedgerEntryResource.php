<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SupplierLedgerEntryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'type'           => $this->type,
            'amount'         => (float) $this->amount,
            'balance_after'  => (float) $this->balance_after,
            'reference_type' => $this->reference_type,
            'reference_id'   => $this->reference_id,
            'date'           => $this->date?->toDateString(),
            'description'    => $this->description,
            'created_by'     => $this->createdBy?->name,
            'created_at'     => $this->created_at->toISOString(),
        ];
    }
}
