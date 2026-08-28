<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlterationStatusHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'garment_id'  => $this->alteration_garment_id,
            'task_id'     => $this->alteration_task_id,
            'from_status' => $this->from_status,
            'to_status'   => $this->to_status,
            'changed_by'  => $this->whenLoaded('changedBy', fn () => $this->changedBy?->name),
            'notes'       => $this->notes,
            'created_at'  => $this->created_at?->toISOString(),
        ];
    }
}
