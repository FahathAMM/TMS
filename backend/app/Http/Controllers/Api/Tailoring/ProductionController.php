<?php

namespace App\Http\Controllers\Api\Tailoring;

use App\Http\Controllers\Controller;
use App\Models\Tailoring\OrderItem;
use App\Repositories\ProductionRepo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductionController extends Controller
{
    protected string $modelName = 'Item';
    protected OrderItem $model;
    protected ProductionRepo $repo;

    public function __construct(OrderItem $model, ProductionRepo $repo)
    {
        $this->model = $model;
        $this->repo  = $repo;
    }

    public function index(Request $request): JsonResponse
    {
        $items = $this->repo->getData($request);

        // Shape matches UserController@index: the raw paginator under
        // "record", read by ServerDataTable as res.data.record.
        return response()->json([
            'record'  => $items,
            'message' => "{$this->modelName}s retrieved successfully",
            'status'  => true,
        ]);
    }
}
