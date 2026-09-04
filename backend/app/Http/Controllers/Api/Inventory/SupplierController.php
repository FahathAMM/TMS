<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\StoreSupplierContactRequest;
use App\Http\Requests\Supplier\StoreSupplierRequest;
use App\Http\Requests\Supplier\UpdateSupplierRequest;
use App\Http\Resources\SupplierContactResource;
use App\Http\Resources\SupplierLedgerEntryResource;
use App\Http\Resources\SupplierResource;
use App\Models\Inventory\Supplier;
use App\Models\Inventory\SupplierContact;
use App\Repositories\SupplierRepo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    protected string $modelName = 'Supplier';
    protected Supplier $model;
    protected SupplierRepo $repo;

    public function __construct(Supplier $model, SupplierRepo $repo)
    {
        $this->model = $model;
        $this->repo  = $repo;
    }

    public function index(Request $request): JsonResponse
    {
        $suppliers = $this->repo->getData($request);

        return response()->json([
            'data' => SupplierResource::collection($suppliers->items()),
            'meta' => [
                'current_page' => $suppliers->currentPage(),
                'last_page'    => $suppliers->lastPage(),
                'per_page'     => $suppliers->perPage(),
                'total'        => $suppliers->total(),
            ],
        ]);
    }

    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $supplier = $this->repo->store($request->validated());

        return response()->json([
            'message' => "{$this->modelName} created successfully",
            'data'    => new SupplierResource($supplier),
        ], 201);
    }

    public function show(Supplier $supplier): JsonResponse
    {
        return response()->json([
            'data' => new SupplierResource($this->repo->findWithDetails($supplier->id)),
        ]);
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): JsonResponse
    {
        $supplier = $this->repo->update($supplier, $request->validated());

        return response()->json([
            'message' => "{$this->modelName} updated successfully",
            'data'    => new SupplierResource($supplier),
        ]);
    }

    public function destroy(Supplier $supplier): JsonResponse
    {
        $this->repo->destroy($supplier);

        return response()->json(['message' => "{$this->modelName} deleted successfully"]);
    }

    // ─── Ledger ───────────────────────────────────────────────────────────────

    public function ledger(Request $request, Supplier $supplier): JsonResponse
    {
        $entries = $this->repo->getLedger($supplier, $request);

        return response()->json([
            'data'            => SupplierLedgerEntryResource::collection($entries->items()),
            'current_balance' => (float) $supplier->current_balance,
            'meta'            => [
                'current_page' => $entries->currentPage(),
                'last_page'    => $entries->lastPage(),
                'per_page'     => $entries->perPage(),
                'total'        => $entries->total(),
            ],
        ]);
    }

    // ─── Contacts ─────────────────────────────────────────────────────────────

    public function storeContact(StoreSupplierContactRequest $request, Supplier $supplier): JsonResponse
    {
        $contact = $this->repo->storeContact($supplier, $request->validated());

        return response()->json([
            'message' => 'Contact added successfully',
            'data'    => new SupplierContactResource($contact),
        ], 201);
    }

    public function updateContact(StoreSupplierContactRequest $request, Supplier $supplier, SupplierContact $contact): JsonResponse
    {
        abort_if($contact->supplier_id !== $supplier->id, 404);

        $contact = $this->repo->updateContact($contact, $request->validated());

        return response()->json([
            'message' => 'Contact updated successfully',
            'data'    => new SupplierContactResource($contact),
        ]);
    }

    public function destroyContact(Supplier $supplier, SupplierContact $contact): JsonResponse
    {
        abort_if($contact->supplier_id !== $supplier->id, 404);

        $this->repo->destroyContact($contact);

        return response()->json(['message' => 'Contact deleted successfully']);
    }
}
