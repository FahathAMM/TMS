<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\BulkProductRequest;
use App\Http\Requests\Product\StoreMediaRequest;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\StoreSeoRequest;
use App\Http\Requests\Product\StoreVariantRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Requests\Product\UpdateVariantRequest;
use App\Http\Resources\ProductMediaResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductSeoResource;
use App\Http\Resources\ProductSpecificationResource;
use App\Http\Resources\ProductVariantResource;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ProductVariant;
use App\Models\StockAdjustment;
use App\Repositories\ProductRepo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    protected string $modelName = 'Product';
    protected string $routeName = 'products';
    protected bool $isDestroyingAllowed;
    protected Product $model;
    protected ProductRepo $repo;

    public function __construct(Product $model, ProductRepo $repo)
    {
        $this->model              = $model;
        $this->repo               = $repo;
        $this->isDestroyingAllowed = true;
    }

    // ─── Products ─────────────────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $products = $this->repo->getData($request);

        return response()->json([
            'data' => ProductResource::collection($products->items()),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page'    => $products->lastPage(),
                'per_page'     => $products->perPage(),
                'total'        => $products->total(),
            ],
        ]);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->repo->store($request->validated());

        return response()->json([
            'message' => "{$this->modelName} created successfully",
            'data'    => new ProductResource($product),
        ], 201);
    }

    public function show(Product $product): JsonResponse
    {
        return response()->json([
            'data' => new ProductResource($this->repo->findWithDetails($product->id)),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product = $this->repo->update($product, $request->validated());

        return response()->json([
            'message' => "{$this->modelName} updated successfully",
            'data'    => new ProductResource($product),
        ]);
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->repo->destroy($product);

        return response()->json(['message' => "{$this->modelName} deleted successfully"]);
    }

    public function toggleFeatured(Product $product): JsonResponse
    {
        $product = $this->repo->toggleFeatured($product);

        return response()->json([
            'message'     => 'Featured status updated',
            'is_featured' => $product->is_featured,
        ]);
    }

    public function toggleNewArrival(Product $product): JsonResponse
    {
        $product = $this->repo->toggleNewArrival($product);

        return response()->json([
            'message'        => 'New arrival status updated',
            'is_new_arrival' => $product->is_new_arrival,
        ]);
    }

    public function bulkAction(BulkProductRequest $request): JsonResponse
    {
        $count = $this->repo->bulkAction($request->input('ids'), $request->input('action'));

        return response()->json(['message' => "{$count} products updated"]);
    }

    public function stockHistory(Product $product): JsonResponse
    {
        $history = StockAdjustment::with(['user', 'sale'])
            ->where('product_id', $product->id)
            ->latest()
            ->paginate(15);

        return response()->json(['data' => $history]);
    }

    // ─── Media ────────────────────────────────────────────────────────────────

    public function indexMedia(Product $product): JsonResponse
    {
        return response()->json([
            'data' => ProductMediaResource::collection($product->media()->ordered()->get()),
        ]);
    }

    public function storeMedia(StoreMediaRequest $request, Product $product): JsonResponse
    {
        $media = $this->repo->uploadMedia($product, $request->file('file'), $request->validated());

        return response()->json([
            'message' => 'Media uploaded successfully',
            'data'    => new ProductMediaResource($media),
        ], 201);
    }

    public function destroyMedia(Product $product, ProductMedia $media): JsonResponse
    {
        abort_if($media->product_id !== $product->id, 404);

        $this->repo->deleteMedia($media);

        return response()->json(['message' => 'Media deleted successfully']);
    }

    public function reorderMedia(Request $request, Product $product): JsonResponse
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);

        $this->repo->reorderMedia($product, $request->input('ids'));

        return response()->json(['message' => 'Media reordered successfully']);
    }

    public function setPrimaryMedia(Product $product, ProductMedia $media): JsonResponse
    {
        abort_if($media->product_id !== $product->id, 404);

        $this->repo->setPrimaryMedia($product, $media);

        return response()->json(['message' => 'Primary media updated']);
    }

    // ─── SEO ──────────────────────────────────────────────────────────────────

    public function showSeo(Product $product): JsonResponse
    {
        return response()->json([
            'data' => new ProductSeoResource(
                $product->seo ?? $product->seo()->firstOrNew(['product_id' => $product->id])
            ),
        ]);
    }

    public function upsertSeo(StoreSeoRequest $request, Product $product): JsonResponse
    {
        $seo = $this->repo->upsertSeo($product, $request->validated());

        return response()->json([
            'message' => 'SEO data saved',
            'data'    => new ProductSeoResource($seo),
        ]);
    }

    // ─── Specifications ───────────────────────────────────────────────────────

    public function showSpecifications(Product $product): JsonResponse
    {
        return response()->json([
            'data' => ProductSpecificationResource::collection(
                $product->specifications()->ordered()->get()
            ),
        ]);
    }

    // ─── Variants ─────────────────────────────────────────────────────────────

    public function storeVariant(StoreVariantRequest $request, Product $product): JsonResponse
    {
        $variant = $this->repo->storeVariant($product, $request->validated());

        return response()->json([
            'message' => 'Variant created successfully',
            'data'    => new ProductVariantResource($variant),
        ], 201);
    }

    public function updateVariant(UpdateVariantRequest $request, Product $product, ProductVariant $variant): JsonResponse
    {
        abort_if($variant->product_id !== $product->id, 404);

        $variant = $this->repo->updateVariant($variant, $request->validated());

        return response()->json([
            'message' => 'Variant updated successfully',
            'data'    => new ProductVariantResource($variant),
        ]);
    }

    public function destroyVariant(Product $product, ProductVariant $variant): JsonResponse
    {
        abort_if($variant->product_id !== $product->id, 404);

        $this->repo->destroyVariant($variant);

        return response()->json(['message' => 'Variant deleted successfully']);
    }
}
