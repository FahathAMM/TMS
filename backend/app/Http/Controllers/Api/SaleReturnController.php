<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaleReturn\StoreSaleReturnRequest;
use App\Http\Requests\SaleReturn\UpdateSaleReturnRequest;
use App\Http\Resources\SaleReturnResource;
use App\Models\SaleReturn;
use App\Repositories\SaleReturnRepo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaleReturnController extends Controller
{
    protected string $modelName = 'Sale Return';
    protected SaleReturn $model;
    protected SaleReturnRepo $repo;

    public function __construct(SaleReturn $model, SaleReturnRepo $repo)
    {
        $this->model = $model;
        $this->repo  = $repo;
    }

    public function index(Request $request): JsonResponse
    {
        $returns = $this->repo->getData($request);

        return response()->json([
            'data' => SaleReturnResource::collection($returns->items()),
            'meta' => [
                'current_page' => $returns->currentPage(),
                'last_page'    => $returns->lastPage(),
                'per_page'     => $returns->perPage(),
                'total'        => $returns->total(),
            ],
        ]);
    }

    public function store(StoreSaleReturnRequest $request): JsonResponse
    {
        $return = $this->repo->store($request->validated(), $request->user()->id);

        return response()->json([
            'message' => "{$this->modelName} created successfully",
            'data'    => new SaleReturnResource($return),
        ], 201);
    }

    public function show(SaleReturn $saleReturn): JsonResponse
    {
        return response()->json([
            'data' => new SaleReturnResource($this->repo->findWithDetails($saleReturn->id)),
        ]);
    }

    public function update(UpdateSaleReturnRequest $request, SaleReturn $saleReturn): JsonResponse
    {
        $return = $this->repo->update($saleReturn, $request->validated());

        return response()->json([
            'message' => "{$this->modelName} updated successfully",
            'data'    => new SaleReturnResource($return),
        ]);
    }

    public function destroy(SaleReturn $saleReturn): JsonResponse
    {
        $this->repo->destroy($saleReturn);

        return response()->json(['message' => "{$this->modelName} deleted successfully"]);
    }

    public function approve(Request $request, SaleReturn $saleReturn): JsonResponse
    {
        $return = $this->repo->approve($saleReturn, $request->user()->id);

        return response()->json([
            'message' => 'Return approved and stock restored',
            'data'    => new SaleReturnResource($return),
        ]);
    }

    public function reject(Request $request, SaleReturn $saleReturn): JsonResponse
    {
        $return = $this->repo->reject($saleReturn, $request->user()->id);

        return response()->json([
            'message' => 'Return rejected',
            'data'    => new SaleReturnResource($return),
        ]);
    }
}
