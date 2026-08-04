<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\AdjustStockRequest;
use App\Http\Resources\ProductResource;
use App\Http\Resources\StockAdjustmentResource;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(private InventoryService $inventoryService) {}

    public function index(Request $request): JsonResponse
    {
        $products = $this->inventoryService->getAll($request->all());

        return response()->json([
            'data' => ProductResource::collection($products->items()),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    public function adjust(AdjustStockRequest $request): JsonResponse
    {
        $adjustment = $this->inventoryService->adjust($request->validated(), $request->user()->id);

        return response()->json([
            'message' => 'Stock adjusted successfully',
            'data' => new StockAdjustmentResource($adjustment->load(['product', 'user'])),
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $history = $this->inventoryService->getHistory($request->all());

        return response()->json([
            'data' => StockAdjustmentResource::collection($history->items()),
            'meta' => [
                'current_page' => $history->currentPage(),
                'last_page' => $history->lastPage(),
                'per_page' => $history->perPage(),
                'total' => $history->total(),
            ],
        ]);
    }

    public function lowStock(): JsonResponse
    {
        $products = $this->inventoryService->getLowStock();

        return response()->json(['data' => ProductResource::collection($products)]);
    }
}
