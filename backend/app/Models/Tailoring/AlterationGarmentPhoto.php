<?php

namespace App\Models\Tailoring;

use App\Models\Administration\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlterationGarmentPhoto extends Model
{
    protected $fillable = ['alteration_garment_id', 'type', 'path', 'uploaded_by'];

    public function garment(): BelongsTo
    {
        return $this->belongsTo(AlterationGarment::class, 'alteration_garment_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->path);
    }
}
