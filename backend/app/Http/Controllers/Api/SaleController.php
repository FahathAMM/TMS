<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sale\StoreSaleRequest;
use App\Http\Resources\SaleResource;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function __construct(private SaleService $saleService) {}

    public function index(Request $request): JsonResponse
    {
        $sales = $this->saleService->getAll($request->all());

        return response()->json([
            'data' => SaleResource::collection($sales->items()),
            'meta' => [
                'current_page' => $sales->currentPage(),
                'last_page' => $sales->lastPage(),
                'per_page' => $sales->perPage(),
                'total' => $sales->total(),
            ],
        ]);
    }

    public function store(StoreSaleRequest $request): JsonResponse
    {
        $sale = $this->saleService->create($request->validated(), $request->user()->id);

        return response()->json([
            'message' => 'Sale completed successfully',
            'data' => new SaleResource($sale),
        ], 201);
    }

    public function show(Sale $sale): JsonResponse
    {
        return response()->json(['data' => new SaleResource($sale->load(['customer', 'user', 'items.product']))]);
    }

    public function cancel(Sale $sale, Request $request): JsonResponse
    {
        $sale = $this->saleService->cancel($sale, $request->user()->id);

        return response()->json([
            'message' => 'Sale cancelled successfully',
            'data' => new SaleResource($sale),
        ]);
    }

    public function printInvoice(Sale $sale): JsonResponse
    {
        return response()->json(['data' => new SaleResource($sale->load(['customer', 'user', 'items.product']))]);
    }
}
