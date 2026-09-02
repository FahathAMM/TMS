<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlterationStatusHistory extends Model
{
    protected $table = 'alteration_status_history';

    protected $fillable = [
        'alteration_order_id', 'alteration_garment_id', 'alteration_task_id',
        'from_status', 'to_status', 'changed_by', 'notes',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(AlterationOrder::class, 'alteration_order_id');
    }

    public function garment(): BelongsTo
    {
        return $this->belongsTo(AlterationGarment::class, 'alteration_garment_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(AlterationTask::class, 'alteration_task_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
