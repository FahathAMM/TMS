<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    protected $fillable = [
        'expense_number', 'category', 'description', 'amount',
        'expense_date', 'payment_method', 'created_by',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'expense_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Expense $expense) {
            if (empty($expense->expense_number)) {
                $expense->expense_number = 'EXP-' . strtoupper(uniqid());
            }
        });
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
