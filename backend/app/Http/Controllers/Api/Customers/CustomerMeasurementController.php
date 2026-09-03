<?php

namespace App\Http\Controllers\Api\Customers;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerMeasurement\UpdateCustomerMeasurementsRequest;
use App\Http\Resources\CustomerMeasurementResource;
use App\Models\Customers\Customer;
use Illuminate\Http\JsonResponse;

class CustomerMeasurementController extends Controller
{
    public function index(Customer $customer): JsonResponse
    {
        $measurements = $customer->measurements()->with('measurementField.measurementType')->get();

        return response()->json(['data' => CustomerMeasurementResource::collection($measurements)]);
    }

    /**
     * Bulk upsert — latest value per measurement type, matching the
     * customer_measurements unique(customer_id, measurement_type_id) constraint.
     */
    public function update(UpdateCustomerMeasurementsRequest $request, Customer $customer): JsonResponse
    {
        foreach ($request->validated('measurements') as $measurement) {
            $customer->measurements()->updateOrCreate(
                ['measurement_field_id' => $measurement['measurement_field_id']],
                ['value' => $measurement['value'], 'recorded_at' => now()]
            );
        }

        return response()->json([
            'message' => 'Measurements saved successfully',
            'data'    => CustomerMeasurementResource::collection(
                $customer->measurements()->with('measurementField.measurementType')->get()
            ),
        ]);
    }
}
