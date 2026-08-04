<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MeasurementType\StoreMeasurementTypeRequest;
use App\Http\Requests\MeasurementType\UpdateMeasurementTypeRequest;
use App\Http\Resources\MeasurementTypeResource;
use App\Models\MeasurementType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeasurementTypeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = MeasurementType::query()->orderBy('category')->orderBy('name');

        if ($category = $request->get('category')) {
            $query->where('category', $category);
        }

        return response()->json(['data' => MeasurementTypeResource::collection($query->get())]);
    }

    public function store(StoreMeasurementTypeRequest $request): JsonResponse
    {
        $type = MeasurementType::create($request->validated());

        return response()->json([
            'message' => 'Measurement type created successfully',
            'data'    => new MeasurementTypeResource($type),
        ], 201);
    }

    public function update(UpdateMeasurementTypeRequest $request, MeasurementType $measurementType): JsonResponse
    {
        $measurementType->update($request->validated());

        return response()->json([
            'message' => 'Measurement type updated successfully',
            'data'    => new MeasurementTypeResource($measurementType),
        ]);
    }

    public function destroy(MeasurementType $measurementType): JsonResponse
    {
        $measurementType->delete();

        return response()->json(['message' => 'Measurement type deleted successfully']);
    }
}
