<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MeasurementType\StoreMeasurementTypeRequest;
use App\Http\Requests\MeasurementType\UpdateMeasurementTypeRequest;
use App\Http\Requests\MeasurementType\UploadMeasurementTypeImageRequest;
use App\Http\Resources\MeasurementTypeResource;
use App\Models\MeasurementType;
use App\Services\MeasurementTypeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeasurementTypeController extends Controller
{
    public function __construct(private readonly MeasurementTypeService $service) {}

    public function index(Request $request): JsonResponse
    {
        $query = MeasurementType::query()->with('fields')->orderBy('name');

        return response()->json(['data' => MeasurementTypeResource::collection($query->get())]);
    }

    public function show(MeasurementType $measurementType): JsonResponse
    {
        return response()->json(['data' => new MeasurementTypeResource($measurementType->load('fields'))]);
    }

    public function store(StoreMeasurementTypeRequest $request): JsonResponse
    {
        $type = $this->service->create($request->validated());

        return response()->json([
            'message' => 'Measurement type created successfully',
            'data'    => new MeasurementTypeResource($type),
        ], 201);
    }

    public function update(UpdateMeasurementTypeRequest $request, MeasurementType $measurementType): JsonResponse
    {
        $type = $this->service->update($measurementType, $request->validated());

        return response()->json([
            'message' => 'Measurement type updated successfully',
            'data'    => new MeasurementTypeResource($type),
        ]);
    }

    public function uploadImage(UploadMeasurementTypeImageRequest $request, MeasurementType $measurementType): JsonResponse
    {
        $type = $this->service->uploadImage($measurementType, $request->file('image'));

        return response()->json([
            'message' => 'Measurement type image uploaded successfully',
            'data'    => new MeasurementTypeResource($type),
        ]);
    }

    public function destroy(MeasurementType $measurementType): JsonResponse
    {
        $measurementType->delete();

        return response()->json(['message' => 'Measurement type deleted successfully']);
    }
}
