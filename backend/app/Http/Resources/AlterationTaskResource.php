<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlterationTaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'alteration_type_id'  => $this->alteration_type_id,
            'description'         => $this->description,
            'price'               => (float) $this->price,
            'quantity'            => $this->quantity,
            'total'               => round((float) $this->price * $this->quantity, 2),
            'status'              => $this->status,
            'started_at'          => $this->started_at?->toISOString(),
            'completed_at'        => $this->completed_at?->toISOString(),
            'notes'               => $this->notes,
            'current_tailor'      => $this->whenLoaded('assignments', fn () => $this->assignments->sortByDesc('assigned_at')->first()?->tailor?->full_name),
            'assignments'         => AlterationTaskAssignmentResource::collection($this->whenLoaded('assignments')),
        ];
    }
}
