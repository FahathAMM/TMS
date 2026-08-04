<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SupplierReturn\StoreSupplierReturnRequest;
use App\Http\Requests\SupplierReturn\UpdateSupplierReturnRequest;
use App\Http\Resources\SupplierReturnResource;
use App\Models\SupplierReturn;
use App\Repositories\SupplierReturnRepo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierReturnController extends Controller
{
    protected string $modelName = 'Supplier Return';
    protected SupplierReturn $model;
    protected SupplierReturnRepo $repo;

    public function __construct(SupplierReturn $model, SupplierReturnRepo $repo)
    {
        $this->model = $model;
        $this->repo  = $repo;
    }

    public function index(Request $request): JsonResponse
    {
        $returns = $this->repo->getData($request);

        return response()->json([
            'data' => SupplierReturnResource::collection($returns->items()),
            'meta' => [
                'current_page' => $returns->currentPage(),
                'last_page'    => $returns->lastPage(),
                'per_page'     => $returns->perPage(),
                'total'        => $returns->total(),
            ],
        ]);
    }

    public function store(StoreSupplierReturnRequest $request): JsonResponse
    {
        $return = $this->repo->store($request->validated(), $request->user()->id);

        return response()->json([
            'message' => "{$this->modelName} created successfully",
            'data'    => new SupplierReturnResource($return),
        ], 201);
    }

    public function show(SupplierReturn $supplierReturn): JsonResponse
    {
        return response()->json([
            'data' => new SupplierReturnResource($this->repo->findWithDetails($supplierReturn->id)),
        ]);
    }

    public function update(UpdateSupplierReturnRequest $request, SupplierReturn $supplierReturn): JsonResponse
    {
        $return = $this->repo->update($supplierReturn, $request->validated());

        return response()->json([
            'message' => "{$this->modelName} updated successfully",
            'data'    => new SupplierReturnResource($return),
        ]);
    }

    public function destroy(SupplierReturn $supplierReturn): JsonResponse
    {
        $this->repo->destroy($supplierReturn);

        return response()->json(['message' => "{$this->modelName} deleted successfully"]);
    }

    public function confirm(Request $request, SupplierReturn $supplierReturn): JsonResponse
    {
        $return = $this->repo->confirm($supplierReturn, $request->user()->id);

        return response()->json([
            'message' => 'Return confirmed and stock updated',
            'data'    => new SupplierReturnResource($return),
        ]);
    }
}
