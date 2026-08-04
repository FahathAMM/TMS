<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'expense_number'  => $this->expense_number,
            'category'        => $this->category,
            'description'     => $this->description,
            'amount'          => (float) $this->amount,
            'expense_date'    => $this->expense_date?->toDateString(),
            'payment_method'  => $this->payment_method,
            'created_by'      => $this->whenLoaded('createdBy', fn () => $this->createdBy?->name),
            'created_at'      => $this->created_at?->toISOString(),
        ];
    }
}
