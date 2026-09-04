<?php

namespace App\Models\Administration;

use Illuminate\Database\Eloquent\Model;

class StoreSetting extends Model
{
    protected $fillable = ['key', 'value', 'group', 'type', 'label', 'sort_order'];

    protected $table = 'store_settings';
}
