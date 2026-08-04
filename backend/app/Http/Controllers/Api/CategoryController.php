<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Repositories\CategoryRepo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    protected string $modelName = 'Category';
    protected string $routeName = 'categories';
    protected bool $isDestroyingAllowed;
    protected Category $model;
    protected CategoryRepo $repo;

    public function __construct(Category $model, CategoryRepo $repo)
    {
        $this->model               = $model;
        $this->repo                = $repo;
        $this->isDestroyingAllowed = true;
    }

    public function index(Request $request): JsonResponse
    {
        $categories = $this->repo->getData($request);

        if ($request->boolean('all') || $request->boolean('tree')) {
            return response()->json(['data' => CategoryResource::collection($categories)]);
        }

        return response()->json([
            'data' => CategoryResource::collection($categories->items()),
            'meta' => [
                'current_page' => $categories->currentPage(),
                'last_page'    => $categories->lastPage(),
                'per_page'     => $categories->perPage(),
                'total'        => $categories->total(),
            ],
        ]);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->repo->store($request->validated());

        return response()->json([
            'message' => "{$this->modelName} created successfully",
            'data'    => new CategoryResource($category),
        ], 201);
    }

    public function show(Category $category): JsonResponse
    {
        return response()->json([
            'data' => new CategoryResource($category->loadCount('products')),
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $category = $this->repo->update($category, $request->validated());

        return response()->json([
            'message' => "{$this->modelName} updated successfully",
            'data'    => new CategoryResource($category),
        ]);
    }

    public function destroy(Category $category): JsonResponse
    {
        try {
            $this->repo->destroy($category);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => "{$this->modelName} deleted successfully"]);
    }
}
