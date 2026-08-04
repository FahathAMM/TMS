<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'company'          => $this->company,
            'phone'            => $this->phone,
            'email'            => $this->email,
            'address'          => $this->address,
            'city'             => $this->city,
            'tax_number'       => $this->tax_number,
            'opening_balance'  => (float) $this->opening_balance,
            'current_balance'  => (float) $this->current_balance,
            'status'           => $this->status,
            'notes'            => $this->notes,
            'purchases_count'  => $this->whenCounted('purchases'),
            'primary_contact'  => new SupplierContactResource($this->whenLoaded('primaryContact')),
            'contacts'         => SupplierContactResource::collection($this->whenLoaded('contacts')),
            'created_at'       => $this->created_at->toISOString(),
            'updated_at'       => $this->updated_at->toISOString(),
        ];
    }
}
