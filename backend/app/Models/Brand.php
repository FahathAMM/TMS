<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Brand extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($model) => $model->slug = Str::slug($model->name));
        static::updating(fn ($model) => $model->slug = Str::slug($model->name));
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
