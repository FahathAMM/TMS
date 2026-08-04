<?php

namespace App\Models;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'sku',
        'barcode',
        'name',
        'slug',
        'type',
        'status',
        'category_id',
        'brand_id',
        'description',
        'short_description',
        'cost_price',
        'selling_price',
        'compare_price',
        'stock_quantity',
        'low_stock_threshold',
        'image',
        'weight',
        'weight_unit',
        'length',
        'width',
        'height',
        'is_active',
        'is_featured',
        'is_best_seller',
        'is_new_arrival',
        'is_digital',
        'sort_order',
        'published_at',
        'unit_of_measure',
    ];

    protected $casts = [
        'status'       => ProductStatus::class,
        'type'         => ProductType::class,
        'is_active'       => 'boolean',
        'is_featured'     => 'boolean',
        'is_best_seller'  => 'boolean',
        'is_new_arrival'  => 'boolean',
        'is_digital'      => 'boolean',
        'cost_price'   => 'decimal:2',
        'selling_price' => 'decimal:2',
        'compare_price' => 'decimal:2',
        'published_at'  => 'datetime',
        'sort_order'    => 'integer',
        'stock_quantity' => 'decimal:3',
        'low_stock_threshold' => 'decimal:3',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Product $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->slug)) {
                $model->slug = static::generateUniqueSlug($model->name);
            }
        });

        static::updating(function (Product $model): void {
            if (empty($model->slug)) {
                $model->slug = static::generateUniqueSlug($model->name, $model->id);
            }
        });
    }

    public static function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i    = 1;

        while (
            static::where('slug', $slug)
                ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function seo(): HasOne
    {
        return $this->hasOne(ProductSeo::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(ProductMedia::class)->orderBy('sort_order')->orderBy('id');
    }

    public function specifications(): HasMany
    {
        return $this->hasMany(ProductSpecification::class)->orderBy('group')->orderBy('sort_order');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'product_tag');
    }

    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class, 'product_attributes');
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function stockAdjustments(): HasMany
    {
        return $this->hasMany(StockAdjustment::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function orderItemMaterials(): HasMany
    {
        return $this->hasMany(OrderItemMaterial::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ProductStatus::Active->value)
                     ->whereNotNull('published_at')
                     ->where('published_at', '<=', now());
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $q) use ($term): void {
            $q->where('name', 'LIKE', "%{$term}%")
              ->orWhere('sku', 'LIKE', "%{$term}%")
              ->orWhere('description', 'LIKE', "%{$term}%")
              ->orWhere('short_description', 'LIKE', "%{$term}%");
        });
    }

    public function scopeOfType(Builder $query, ProductType $type): Builder
    {
        return $query->where('type', $type->value);
    }

    public function scopeOfStatus(Builder $query, ProductStatus $status): Builder
    {
        return $query->where('status', $status->value);
    }

    public function scopeWithFullDetails(Builder $query): Builder
    {
        return $query->with([
            'category',
            'brand',
            'seo',
            'media'        => fn($q) => $q->ordered(),
            'specifications' => fn($q) => $q->ordered(),
            'variants'     => fn($q) => $q->active()->with('attributeValues.attribute'),
            'tags',
            'attributes.values',
        ]);
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getIsVariableAttribute(): bool
    {
        return $this->type === ProductType::Variable;
    }

    public function getIsSimpleAttribute(): bool
    {
        return $this->type === ProductType::Simple;
    }

    public function getPrimaryImageAttribute(): ?ProductMedia
    {
        return $this->media->where('is_primary', true)->first()
            ?? $this->media->where('type', 'image')->first();
    }

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) return null;
        // Already an absolute URL (e.g. placeholder stored during seeding)
        if (str_starts_with($this->image, 'http')) return $this->image;
        return asset('storage/' . $this->image);
    }

    public function isLowStock(): bool
    {
        return $this->stock_quantity <= $this->low_stock_threshold;
    }
}
