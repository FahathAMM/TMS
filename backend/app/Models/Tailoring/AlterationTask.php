<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlterationTask extends Model
{
    protected $fillable = [
        'alteration_garment_id', 'alteration_type_id', 'description',
        'price', 'quantity', 'status', 'started_at', 'completed_at', 'notes',
    ];

    protected $casts = [
        'price'        => 'float',
        'quantity'     => 'integer',
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function garment(): BelongsTo
    {
        return $this->belongsTo(AlterationGarment::class, 'alteration_garment_id');
    }

    public function alterationType(): BelongsTo
    {
        return $this->belongsTo(AlterationType::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(AlterationTaskAssignment::class);
    }

    public function currentTailor(): ?Tailor
    {
        return $this->assignments()->latest('assigned_at')->first()?->tailor;
    }
}
