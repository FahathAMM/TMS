<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TailorAssignment extends Model
{
    protected $fillable = ['order_item_id', 'tailor_id', 'assigned_role', 'assigned_at', 'completed_at'];

    protected $casts = [
        'assigned_at'  => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function tailor(): BelongsTo
    {
        return $this->belongsTo(Tailor::class);
    }
}
