<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlterationTaskAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'tailor_id'    => $this->tailor_id,
            'tailor_name'  => $this->whenLoaded('tailor', fn () => $this->tailor?->full_name),
            'assigned_at'  => $this->assigned_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
        ];
    }
}
