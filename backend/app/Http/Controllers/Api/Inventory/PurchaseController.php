<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Purchase\ReceivePurchaseRequest;
use App\Http\Requests\Purchase\StorePurchasePaymentRequest;
use App\Http\Requests\Purchase\StorePurchaseRequest;
use App\Http\Requests\Purchase\UpdatePurchaseRequest;
use App\Http\Resources\PurchasePaymentResource;
use App\Http\Resources\PurchaseResource;
use App\Models\Inventory\Purchase;
use App\Repositories\PurchaseRepo;
use App\Services\AuthUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    protected string $modelName = 'Purchase';
    protected Purchase $model;
    protected PurchaseRepo $repo;

    public function __construct(Purchase $model, PurchaseRepo $repo)
    {
        $this->model = $model;
        $this->repo  = $repo;
    }

    public function index(Request $request): JsonResponse
    {
        $purchases = $this->repo->getData($request);

        return response()->json([
            'data' => PurchaseResource::collection($purchases->items()),
            'meta' => [
                'current_page' => $purchases->currentPage(),
                'last_page'    => $purchases->lastPage(),
                'per_page'     => $purchases->perPage(),
                'total'        => $purchases->total(),
            ],
        ]);
    }

    public function store(StorePurchaseRequest $request): JsonResponse
    {
        $purchase = $this->repo->store($request->validated(), AuthUser::id());

        return response()->json([
            'message' => "{$this->modelName} created successfully",
            'data'    => new PurchaseResource($purchase),
        ], 201);
    }

    public function show(Purchase $purchase): JsonResponse
    {
        return response()->json([
            'data' => new PurchaseResource($this->repo->findWithDetails($purchase->id)),
        ]);
    }

    public function update(UpdatePurchaseRequest $request, Purchase $purchase): JsonResponse
    {
        $purchase = $this->repo->update($purchase, $request->validated(), AuthUser::id());

        return response()->json([
            'message' => "{$this->modelName} updated successfully",
            'data'    => new PurchaseResource($purchase),
        ]);
    }

    public function destroy(Purchase $purchase): JsonResponse
    {
        $this->repo->destroy($purchase);

        return response()->json(['message' => "{$this->modelName} deleted successfully"]);
    }

    // ─── Receive ──────────────────────────────────────────────────────────────

    public function receive(ReceivePurchaseRequest $request, Purchase $purchase): JsonResponse
    {
        $purchase = $this->repo->receiveItems($purchase, $request->input('items'), AuthUser::id());

        return response()->json([
            'message' => 'Items received and stock updated',
            'data'    => new PurchaseResource($purchase),
        ]);
    }

    // ─── Payments ─────────────────────────────────────────────────────────────

    public function payments(Purchase $purchase): JsonResponse
    {
        $payments = $purchase->payments()->with('createdBy')->latest('payment_date')->get();

        return response()->json([
            'data' => PurchasePaymentResource::collection($payments),
        ]);
    }

    public function storePayment(StorePurchasePaymentRequest $request, Purchase $purchase): JsonResponse
    {
        $payment = $this->repo->storePayment($purchase, $request->validated(), AuthUser::id());

        return response()->json([
            'message' => 'Payment recorded successfully',
            'data'    => new PurchasePaymentResource($payment),
        ], 201);
    }
}
