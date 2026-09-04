<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\BulkProductRequest;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Inventory\Product;
use App\Models\Inventory\StockAdjustment;
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

    // ─── SEO ──────────────────────────────────────────────────────────────────

    // ─── Specifications ───────────────────────────────────────────────────────

    // ─── Variants ─────────────────────────────────────────────────────────────

}
