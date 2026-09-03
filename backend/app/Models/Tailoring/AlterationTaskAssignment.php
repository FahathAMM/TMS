<?php

namespace App\Models\Tailoring;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlterationTaskAssignment extends Model
{
    protected $fillable = ['alteration_task_id', 'tailor_id', 'assigned_at', 'completed_at'];

    protected $casts = [
        'assigned_at'  => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(AlterationTask::class, 'alteration_task_id');
    }

    public function tailor(): BelongsTo
    {
        return $this->belongsTo(Tailor::class);
    }
}
