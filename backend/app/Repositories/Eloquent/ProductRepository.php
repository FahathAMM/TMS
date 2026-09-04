<?php

namespace App\Repositories\Eloquent;

use App\Models\Inventory\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository implements ProductRepositoryInterface
{
    public function all(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Product::with(['category', 'brand', 'tags']);

        // Status filter
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Category filter
        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // Brand filter
        if (! empty($filters['brand_id'])) {
            $query->where('brand_id', $filters['brand_id']);
        }

        // Featured filter
        if (isset($filters['is_featured'])) {
            $query->where('is_featured', (bool) $filters['is_featured']);
        }

        // Type filter
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Search
        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Price range
        if (isset($filters['price_min'])) {
            $query->where('selling_price', '>=', $filters['price_min']);
        }
        if (isset($filters['price_max'])) {
            $query->where('selling_price', '<=', $filters['price_max']);
        }

        // is_active filter
        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        // Sorting
        $sort = $filters['sort'] ?? 'newest';
        match ($sort) {
            'price_asc'  => $query->orderBy('selling_price', 'asc'),
            'price_desc' => $query->orderBy('selling_price', 'desc'),
            'name_asc'   => $query->orderBy('name', 'asc'),
            'name_desc'  => $query->orderBy('name', 'desc'),
            default      => $query->latest(),
        };

        return $query->paginate($perPage);
    }

    public function findById(int $id): ?Product
    {
        return Product::find($id);
    }

    public function findBySlug(string $slug): ?Product
    {
        return Product::where('slug', $slug)->first();
    }

    public function create(array $data): Product
    {
        return Product::create($data);
    }

    public function update(int $id, array $data): Product
    {
        $product = Product::findOrFail($id);
        $product->update($data);
        return $product->fresh();
    }

    public function delete(int $id): bool
    {
        $product = Product::findOrFail($id);
        return (bool) $product->delete();
    }

    public function findWithDetails(int $id): ?Product
    {
        return Product::with([
            'category',
            'brand',
            'seo',
            'media'          => fn($q) => $q->ordered(),
            'specifications' => fn($q) => $q->ordered(),
            'variants'       => fn($q) => $q->active()->with('attributeValues.attribute'),
            'tags',
            'attributes.values',
        ])->find($id);
    }

    public function search(string $term, array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 20;

        return Product::with(['category', 'brand'])
            ->search($term)
            ->when(
                ! empty($filters['status']),
                fn($q) => $q->where('status', $filters['status'])
            )
            ->latest()
            ->paginate($perPage);
    }

    public function getFeatured(int $limit = 10): Collection
    {
        return Product::with(['category', 'brand', 'media'])
            ->featured()
            ->active()
            ->orderBy('sort_order')
            ->limit($limit)
            ->get();
    }

    public function getByCategory(int $categoryId, bool $includeDescendants = true): LengthAwarePaginator
    {
        $query = Product::with(['category', 'brand']);

        if ($includeDescendants) {
            $category = \App\Models\Inventory\Category::find($categoryId);
            if ($category) {
                $descendantIds = $category->getDescendants()->pluck('id')->push($categoryId);
                $query->whereIn('category_id', $descendantIds);
            } else {
                $query->where('category_id', $categoryId);
            }
        } else {
            $query->where('category_id', $categoryId);
        }

        return $query->latest()->paginate(20);
    }
}
