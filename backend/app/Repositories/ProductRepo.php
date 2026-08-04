<?php

namespace App\Repositories;

use App\Enums\MediaType;
use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ProductVariant;
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
            'tags',
            'media' => fn($q) => $q->where('is_primary', true)->orderBy('sort_order'),
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
            'seo',
            'media'          => fn($q) => $q->ordered(),
            'specifications' => fn($q) => $q->ordered(),
            'variants'       => fn($q) => $q->active()->with('attributeValues.attribute'),
            'tags',
            'attributes.values',
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

            $this->syncTags($product, $data['tags'] ?? []);
            $this->syncAttributes($product, $data['attributes'] ?? []);
            $this->upsertSeo($product, $data['seo'] ?? []);
            $this->syncSpecifications($product, $data['specifications'] ?? []);
            $this->processVariants($product, $data['variants'] ?? []);

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

            if (array_key_exists('tags', $data)) {
                $this->syncTags($product, $data['tags']);
            }
            if (array_key_exists('attributes', $data)) {
                $this->syncAttributes($product, $data['attributes']);
            }
            if (array_key_exists('seo', $data)) {
                $this->upsertSeo($product, $data['seo']);
            }
            if (array_key_exists('specifications', $data)) {
                $this->syncSpecifications($product, $data['specifications']);
            }
            if (array_key_exists('variants', $data)) {
                $this->processVariants($product, $data['variants']);
            }

            return $this->findWithDetails($product->id);
        });
    }

    public function destroy(Product $product): void
    {
        DB::transaction(function () use ($product): void {
            $product->load('media');

            foreach ($product->media as $media) {
                Storage::disk('public')->delete($media->file_path);
            }

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
            $products = Product::with('media')->whereIn('id', $ids)->get();
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

    public function uploadMedia(Product $product, UploadedFile $file, array $meta = []): ProductMedia
    {
        $mime  = $file->getMimeType() ?? '';
        $type  = str_starts_with($mime, 'video/') ? MediaType::Video : MediaType::Image;
        $path  = $file->store("products/{$product->id}/media", 'public');

        if (! empty($meta['is_primary'])) {
            $product->media()->update(['is_primary' => false]);
        }

        return $product->media()->create([
            'type'       => $type->value,
            'file_name'  => $file->getClientOriginalName(),
            'file_path'  => $path,
            'file_url'   => Storage::disk('public')->url($path),
            'mime_type'  => $mime,
            'file_size'  => $file->getSize(),
            'alt'        => $meta['alt'] ?? null,
            'title'      => $meta['title'] ?? null,
            'sort_order' => $meta['sort_order'] ?? $product->media()->count(),
            'is_primary' => (bool) ($meta['is_primary'] ?? false),
            'variant_id' => $meta['variant_id'] ?? null,
        ]);
    }

    public function deleteMedia(ProductMedia $media): void
    {
        Storage::disk('public')->delete($media->file_path);
        $media->delete();
    }

    public function reorderMedia(Product $product, array $orderedIds): void
    {
        foreach ($orderedIds as $position => $mediaId) {
            $product->media()->where('id', $mediaId)->update(['sort_order' => $position]);
        }
    }

    public function setPrimaryMedia(Product $product, ProductMedia $media): void
    {
        $product->media()->update(['is_primary' => false]);
        $media->update(['is_primary' => true]);
    }

    // ─── SEO ──────────────────────────────────────────────────────────────────

    public function upsertSeo(Product $product, array $data): mixed
    {
        if (empty($data)) {
            return null;
        }

        return $product->seo()->updateOrCreate(
            ['product_id' => $product->id],
            $data
        );
    }

    // ─── Variants ─────────────────────────────────────────────────────────────

    public function storeVariant(Product $product, array $data): ProductVariant
    {
        return DB::transaction(function () use ($product, $data): ProductVariant {
            if (empty($data['sku'])) {
                $data['sku'] = $this->generateVariantSku($product);
            }

            $valueIds = $data['attribute_value_ids'] ?? [];
            unset($data['attribute_value_ids']);

            /** @var ProductVariant $variant */
            $variant = $product->variants()->create($data);

            if (! empty($valueIds)) {
                $variant->attributeValues()->sync($valueIds);
            }

            return $variant->load('attributeValues.attribute');
        });
    }

    public function updateVariant(ProductVariant $variant, array $data): ProductVariant
    {
        return DB::transaction(function () use ($variant, $data): ProductVariant {
            $valueIds = $data['attribute_value_ids'] ?? null;
            unset($data['attribute_value_ids']);

            $variant->update($data);

            if ($valueIds !== null) {
                $variant->attributeValues()->sync($valueIds);
            }

            return $variant->fresh('attributeValues.attribute');
        });
    }

    public function destroyVariant(ProductVariant $variant): void
    {
        $variant->delete();
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    private function syncTags(Product $product, array $tagIds): void
    {
        $product->tags()->sync($tagIds);
    }

    private function syncAttributes(Product $product, array $attributeIds): void
    {
        $product->attributes()->sync($attributeIds);
    }

    private function syncSpecifications(Product $product, array $specs): void
    {
        $product->specifications()->delete();

        foreach ($specs as $index => $spec) {
            $product->specifications()->create([
                'group'      => $spec['group'] ?? 'General',
                'label'      => $spec['label'],
                'value'      => $spec['value'],
                'sort_order' => $spec['sort_order'] ?? $index,
            ]);
        }
    }

    private function processVariants(Product $product, array $variants): void
    {
        foreach ($variants as $variantData) {
            if (isset($variantData['id'])) {
                $variant = ProductVariant::where('id', $variantData['id'])
                    ->where('product_id', $product->id)
                    ->first();

                if ($variant) {
                    $this->updateVariant($variant, $variantData);
                }
            } else {
                $this->storeVariant($product, $variantData);
            }
        }
    }

    private function generateSku(): string
    {
        do {
            $sku = 'PRD-' . strtoupper(Str::random(8));
        } while (Product::where('sku', $sku)->exists());

        return $sku;
    }

    private function generateVariantSku(Product $product): string
    {
        $base = strtoupper(substr(preg_replace('/[^A-Z0-9]/i', '', $product->name), 0, 4));

        do {
            $sku = $base . '-V-' . strtoupper(Str::random(6));
        } while (ProductVariant::where('sku', $sku)->exists());

        return $sku;
    }
}
