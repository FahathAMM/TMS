<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'image',
        'path',
        'depth',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'depth'      => 'integer',
        'sort_order' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Category $model): void {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
            $model->computePathAndDepth();
        });

        static::updating(function (Category $model): void {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
            $model->computePathAndDepth();
        });
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeWithDepth(Builder $query): Builder
    {
        return $query->orderBy('depth')->orderBy('sort_order');
    }

    // ─── Methods ──────────────────────────────────────────────────────────────

    public function getAncestors(): Collection
    {
        if (empty($this->path)) {
            return new Collection();
        }

        $ids = array_filter(explode('/', $this->path));
        // Remove own ID if present
        $ids = array_filter($ids, fn($id) => (int)$id !== $this->id);

        if (empty($ids)) {
            return new Collection();
        }

        return static::whereIn('id', $ids)
            ->orderBy('depth')
            ->get();
    }

    public function getDescendants(): Collection
    {
        if (empty($this->path)) {
            $searchPath = $this->id . '/';
        } else {
            $searchPath = $this->path . $this->id . '/';
        }

        return static::where('path', 'LIKE', $searchPath . '%')
            ->orWhere('path', 'LIKE', '%/' . $this->id . '/%')
            ->get();
    }

    public function isRoot(): bool
    {
        return $this->depth === 0 || is_null($this->parent_id);
    }

    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    // ─── Internal helpers ─────────────────────────────────────────────────────

    protected function computePathAndDepth(): void
    {
        if (is_null($this->parent_id)) {
            $this->depth = 0;
            $this->path  = '';
        } else {
            $parent = static::find($this->parent_id);
            if ($parent) {
                $this->depth = $parent->depth + 1;
                $this->path  = ($parent->path ?? '') . $parent->id . '/';
            } else {
                $this->depth = 0;
                $this->path  = '';
            }
        }
    }
}
