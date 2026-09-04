<?php

namespace App\Repositories;

use App\Enums\MediaType;
use App\Enums\ProductStatus;
use App\Models\Inventory\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductRepo
{
    // ─── Listing ──────────────────────────────────────────────────────────────

    public function getData(Request $request): LengthAwarePaginator
    {
        $query = Product::with([
            'category',
            'brand',
        ]);

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('name', 'LIKE', "%{$term}%")
                  ->orWhere('sku', 'LIKE', "%{$term}%")
                  ->orWhere('barcode', 'LIKE', "%{$term}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('is_featured')) {
            $query->where('is_featured', $request->boolean('is_featured'));
        }

        if ($request->filled('price_min')) {
            $query->where('selling_price', '>=', $request->price_min);
        }

        if ($request->filled('price_max')) {
            $query->where('selling_price', '<=', $request->price_max);
        }

        if ($request->boolean('low_stock')) {
            $query->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
        }

        match ($request->input('sort', 'newest')) {
            'price_asc'  => $query->orderBy('selling_price', 'asc'),
            'price_desc' => $query->orderBy('selling_price', 'desc'),
            'name_asc'   => $query->orderBy('name', 'asc'),
            'name_desc'  => $query->orderBy('name', 'desc'),
            default      => $query->latest(),
        };

        return $query->paginate($request->input('per_page', 15));
    }

    public function findWithDetails(int $id): Product
    {
        return Product::with([
            'category',
            'brand',
        ])->findOrFail($id);
    }

    // ─── CRUD ─────────────────────────────────────────────────────────────────

    public function store(array $data): Product
    {
        return DB::transaction(function () use ($data): Product {
            if (empty($data['sku'])) {
                $data['sku'] = $this->generateSku();
            }

            // Default to active so products appear in POS and storefront immediately
            $data['status'] = $data['status'] ?? 'active';

            if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
                $data['image'] = $data['image']->store('products', 'public');
            }

            $product = Product::create($data);

            return $this->findWithDetails($product->id);
        });
    }

    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data): Product {
            if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
                if ($product->image) {
                    Storage::disk('public')->delete($product->image);
                }
                $data['image'] = $data['image']->store('products', 'public');
            }

            $product->update($data);

            return $this->findWithDetails($product->id);
        });
    }

    public function destroy(Product $product): void
    {
        DB::transaction(function () use ($product): void {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }

            $product->delete();
        });
    }

    // ─── Status & Bulk ────────────────────────────────────────────────────────

    public function toggleFeatured(Product $product): Product
    {
        $product->update(['is_featured' => ! $product->is_featured]);

        return $product->fresh();
    }

    public function toggleNewArrival(Product $product): Product
    {
        $product->update(['is_new_arrival' => ! $product->is_new_arrival]);

        return $product->fresh();
    }

    public function bulkAction(array $ids, string $action): int
    {
        if ($action === 'delete') {
            $products = Product::whereIn('id', $ids)->get();
            $count    = 0;

            DB::transaction(function () use ($products, &$count): void {
                foreach ($products as $product) {
                    foreach ($product->media as $media) {
                        Storage::disk('public')->delete($media->file_path);
                    }
                    if ($product->image) {
                        Storage::disk('public')->delete($product->image);
                    }
                    $product->delete();
                    $count++;
                }
            });

            return $count;
        }

        $statusValues = array_column(ProductStatus::cases(), 'value');

        if (in_array($action, $statusValues)) {
            return Product::whereIn('id', $ids)->update(['status' => $action]);
        }

        return 0;
    }

    // ─── Media ────────────────────────────────────────────────────────────────

    // ─── SEO ──────────────────────────────────────────────────────────────────

    // ─── Variants ─────────────────────────────────────────────────────────────

    // ─── Private helpers ──────────────────────────────────────────────────────

    private function generateSku(): string
    {
        do {
            $sku = 'PRD-' . strtoupper(Str::random(8));
        } while (Product::where('sku', $sku)->exists());

        return $sku;
    }

}
