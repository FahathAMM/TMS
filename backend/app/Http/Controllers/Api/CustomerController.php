<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Customer::query();

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('mobile', 'like', "%{$request->search}%");
            });
        }

        if ($request->boolean('all')) {
            return response()->json(['data' => CustomerResource::collection($query->where('is_active', true)->get())]);
        }

        $customers = $query->latest()->paginate($request->per_page ?? 15);

        return response()->json([
            'data' => CustomerResource::collection($customers->items()),
            'meta' => [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
            ],
        ]);
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $customer = Customer::create($request->validated());

        return response()->json([
            'message' => 'Customer created successfully',
            'data' => new CustomerResource($customer),
        ], 201);
    }

    public function show(Customer $customer): JsonResponse
    {
        return response()->json(['data' => new CustomerResource($customer)]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse
    {
        $customer->update($request->validated());

        return response()->json([
            'message' => 'Customer updated successfully',
            'data' => new CustomerResource($customer),
        ]);
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $customer->delete();

        return response()->json(['message' => 'Customer deleted successfully']);
    }
}
