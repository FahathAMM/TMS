<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SupplierContactResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'supplier_id' => $this->supplier_id,
            'name'        => $this->name,
            'phone'       => $this->phone,
            'email'       => $this->email,
            'designation' => $this->designation,
            'is_primary'  => $this->is_primary,
        ];
    }
}
