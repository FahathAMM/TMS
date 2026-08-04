<?php

namespace App\Services;

use App\Enums\MediaType;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ProductVariant;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductService
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository
    ) {}

    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 15);

        return $this->productRepository->all($filters, $perPage);
    }

    public function getFeatured(int $limit = 10): Collection
    {
        return $this->productRepository->getFeatured($limit);
    }

    public function getByCategory(int $categoryId, bool $includeDescendants = true): LengthAwarePaginator
    {
        return $this->productRepository->getByCategory($categoryId, $includeDescendants);
    }

    public function findById(int $id): ?Product
    {
        return $this->productRepository->findWithDetails($id);
    }

    public function findBySlug(string $slug): ?Product
    {
        return $this->productRepository->findBySlug($slug);
    }

    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data): Product {
            if (empty($data['sku'])) {
                $data['sku'] = $this->generateSku();
            }

            if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
                $data['image'] = $this->storeFile($data['image'], 'products');
            }

            $product = $this->productRepository->create($data);

            $this->syncTags($product, $data['tags'] ?? []);
            $this->syncAttributes($product, $data['attributes'] ?? []);
            $this->upsertSeo($product, $data['seo'] ?? []);
            $this->syncSpecifications($product, $data['specifications'] ?? []);
            $this->syncVariants($product, $data['variants'] ?? []);

            return $this->productRepository->findWithDetails($product->id);
        });
    }

    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data): Product {
            if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
                if ($product->image) {
                    Storage::disk('public')->delete($product->image);
                }
                $data['image'] = $this->storeFile($data['image'], 'products');
            }

            $this->productRepository->update($product->id, $data);

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
                $this->syncVariants($product, $data['variants']);
            }

            return $this->productRepository->findWithDetails($product->id);
        });
    }

    public function delete(Product $product): void
    {
        DB::transaction(function () use ($product): void {
            foreach ($product->media as $media) {
                $this->deleteMediaFile($media);
            }

            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }

            $this->productRepository->delete($product->id);
        });
    }

    public function toggleFeatured(Product $product): Product
    {
        $this->productRepository->update($product->id, [
            'is_featured' => ! $product->is_featured,
        ]);

        return $product->fresh();
    }

    public function bulkUpdateStatus(array $ids, string $status): int
    {
        return Product::whereIn('id', $ids)->update(['status' => $status]);
    }

    public function bulkDelete(array $ids): int
    {
        $products = Product::with('media')->whereIn('id', $ids)->get();

        $count = 0;
        DB::transaction(function () use ($products, &$count): void {
            foreach ($products as $product) {
                foreach ($product->media as $media) {
                    $this->deleteMediaFile($media);
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

    // ─── Media ────────────────────────────────────────────────────────────────

    public function uploadMedia(Product $product, UploadedFile $file, array $meta = []): ProductMedia
    {
        $mime = $file->getMimeType() ?? '';
        $type = str_starts_with($mime, 'video/') ? MediaType::Video : MediaType::Image;

        $path = $file->store("products/{$product->id}/media", 'public');

        $isPrimary = $meta['is_primary'] ?? false;

        if ($isPrimary) {
            $product->media()->update(['is_primary' => false]);
        }

        return $product->media()->create([
            'type'         => $type->value,
            'file_name'    => $file->getClientOriginalName(),
            'file_path'    => $path,
            'file_url'     => Storage::disk('public')->url($path),
            'mime_type'    => $mime,
            'file_size'    => $file->getSize(),
            'alt'          => $meta['alt'] ?? null,
            'title'        => $meta['title'] ?? null,
            'sort_order'   => $meta['sort_order'] ?? $product->media()->count(),
            'is_primary'   => $isPrimary,
            'variant_id'   => $meta['variant_id'] ?? null,
        ]);
    }

    public function deleteMedia(ProductMedia $media): void
    {
        $this->deleteMediaFile($media);
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

    // ─── Variants ─────────────────────────────────────────────────────────────

    public function createVariant(Product $product, array $data): ProductVariant
    {
        return DB::transaction(function () use ($product, $data): ProductVariant {
            if (empty($data['sku'])) {
                $data['sku'] = $this->generateVariantSku($product);
            }

            /** @var ProductVariant $variant */
            $variant = $product->variants()->create($data);

            if (! empty($data['attribute_value_ids'])) {
                $variant->attributeValues()->sync($data['attribute_value_ids']);
            }

            return $variant->load('attributeValues.attribute');
        });
    }

    public function updateVariant(ProductVariant $variant, array $data): ProductVariant
    {
        return DB::transaction(function () use ($variant, $data): ProductVariant {
            $variant->update($data);

            if (array_key_exists('attribute_value_ids', $data)) {
                $variant->attributeValues()->sync($data['attribute_value_ids']);
            }

            return $variant->fresh('attributeValues.attribute');
        });
    }

    public function deleteVariant(ProductVariant $variant): void
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

    private function upsertSeo(Product $product, array $seoData): void
    {
        if (empty($seoData)) {
            return;
        }

        $product->seo()->updateOrCreate(
            ['product_id' => $product->id],
            $seoData
        );
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

    private function syncVariants(Product $product, array $variants): void
    {
        if (empty($variants)) {
            return;
        }

        foreach ($variants as $variantData) {
            if (isset($variantData['id'])) {
                $variant = ProductVariant::find($variantData['id']);
                if ($variant && $variant->product_id === $product->id) {
                    $this->updateVariant($variant, $variantData);
                }
            } else {
                $this->createVariant($product, $variantData);
            }
        }
    }

    private function deleteMediaFile(ProductMedia $media): void
    {
        if ($media->file_path) {
            Storage::disk('public')->delete($media->file_path);
        }
    }

    private function storeFile(UploadedFile $file, string $directory): string
    {
        return $file->store($directory, 'public');
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
