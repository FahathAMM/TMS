<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JournalEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'entry_date'      => $this->entry_date?->toDateString(),
            'description'     => $this->description,
            'reference_type'  => $this->reference_type,
            'reference_id'    => $this->reference_id,
            'lines'           => $this->whenLoaded('lines', fn () =>
                $this->lines->map(fn ($l) => [
                    'account_code' => $l->account->code,
                    'account_name' => $l->account->name,
                    'debit'        => (float) $l->debit,
                    'credit'       => (float) $l->credit,
                ])
            ),
            'created_at'      => $this->created_at?->toISOString(),
        ];
    }
}
