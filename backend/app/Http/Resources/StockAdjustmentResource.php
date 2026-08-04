<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StockAdjustmentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'product' => new ProductResource($this->whenLoaded('product')),
            'user' => new UserResource($this->whenLoaded('user')),
            'type' => $this->type,
            'quantity_before' => $this->quantity_before,
            'quantity_changed' => $this->quantity_changed,
            'quantity_after' => $this->quantity_after,
            'reason' => $this->reason,
            'sale_id' => $this->sale_id,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
