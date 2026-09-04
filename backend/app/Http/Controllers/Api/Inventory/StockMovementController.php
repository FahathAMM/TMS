<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Resources\StockMovementResource;
use App\Models\Inventory\Product;
use App\Models\Inventory\StockMovement;
use App\Repositories\StockMovementRepo;
use App\Services\AuthUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    protected StockMovementRepo $repo;

    public function __construct(StockMovementRepo $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request): JsonResponse
    {
        $movements = $this->repo->getData($request);

        return response()->json([
            'data' => StockMovementResource::collection($movements->items()),
            'meta' => [
                'current_page' => $movements->currentPage(),
                'last_page'    => $movements->lastPage(),
                'per_page'     => $movements->perPage(),
                'total'        => $movements->total(),
            ],
        ]);
    }

    public function forProduct(Request $request, Product $product): JsonResponse
    {
        $movements = $this->repo->getForProduct($product, $request);

        return response()->json([
            'data'           => StockMovementResource::collection($movements->items()),
            'stock_quantity' => $product->stock_quantity,
            'meta'           => [
                'current_page' => $movements->currentPage(),
                'last_page'    => $movements->lastPage(),
                'per_page'     => $movements->perPage(),
                'total'        => $movements->total(),
            ],
        ]);
    }

    public function adjust(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'nullable|exists:product_variants,id',
            'type'       => 'required|in:adjustment_in,adjustment_out',
            'quantity'   => 'required|numeric|min:0.001',
            'date'       => 'nullable|date',
            'notes'      => 'nullable|string|max:255',
        ]);

        $movement = $this->repo->adjust($request->validated(), AuthUser::id());

        return response()->json([
            'message' => 'Stock adjusted successfully',
            'data'    => new StockMovementResource($movement),
        ], 201);
    }
}
