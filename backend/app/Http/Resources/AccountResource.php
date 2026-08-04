<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'code'            => $this->code,
            'name'            => $this->name,
            'type'            => $this->type->value,
            'normal_balance'  => $this->normal_balance,
            'balance'         => $this->balance,
        ];
    }
}
