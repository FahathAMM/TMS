<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tailor extends Model
{
    protected $fillable = ['first_name', 'last_name', 'phone', 'specialization', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function assignments(): HasMany
    {
        return $this->hasMany(TailorAssignment::class);
    }

    public function alterationTaskAssignments(): HasMany
    {
        return $this->hasMany(AlterationTaskAssignment::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}
