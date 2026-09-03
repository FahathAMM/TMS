<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Brand\StoreBrandRequest;
use App\Http\Requests\Brand\UpdateBrandRequest;
use App\Http\Resources\BrandResource;
use App\Models\Inventory\Brand;
use App\Repositories\BrandRepo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    protected string $modelName = 'Brand';
    protected string $routeName = 'brands';
    protected bool $isDestroyingAllowed;
    protected Brand $model;
    protected BrandRepo $repo;

    public function __construct(Brand $model, BrandRepo $repo)
    {
        $this->model               = $model;
        $this->repo                = $repo;
        $this->isDestroyingAllowed = true;
    }

    public function index(Request $request): JsonResponse
    {
        $brands = $this->repo->getData($request);

        if ($request->boolean('all')) {
            return response()->json(['data' => BrandResource::collection($brands)]);
        }

        return response()->json([
            'data' => BrandResource::collection($brands->items()),
            'meta' => [
                'current_page' => $brands->currentPage(),
                'last_page'    => $brands->lastPage(),
                'per_page'     => $brands->perPage(),
                'total'        => $brands->total(),
            ],
        ]);
    }

    public function store(StoreBrandRequest $request): JsonResponse
    {
        $brand = $this->repo->store($request->validated());

        return response()->json([
            'message' => "{$this->modelName} created successfully",
            'data'    => new BrandResource($brand),
        ], 201);
    }

    public function show(Brand $brand): JsonResponse
    {
        return response()->json([
            'data' => new BrandResource($brand->loadCount('products')),
        ]);
    }

    public function update(UpdateBrandRequest $request, Brand $brand): JsonResponse
    {
        $brand = $this->repo->update($brand, $request->validated());

        return response()->json([
            'message' => "{$this->modelName} updated successfully",
            'data'    => new BrandResource($brand),
        ]);
    }

    public function destroy(Brand $brand): JsonResponse
    {
        try {
            $this->repo->destroy($brand);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => "{$this->modelName} deleted successfully"]);
    }
}
