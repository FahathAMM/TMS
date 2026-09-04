<?php

namespace App\Http\Controllers\Api\Tailoring;

use App\Http\Controllers\Controller;
use App\Http\Requests\MeasurementType\StoreMeasurementTypeRequest;
use App\Http\Requests\MeasurementType\UpdateMeasurementTypeRequest;
use App\Http\Requests\MeasurementType\UploadMeasurementTypeImageRequest;
use App\Http\Resources\MeasurementTypeResource;
use App\Models\Tailoring\MeasurementType;
use App\Repositories\MeasurementTypeRepo;
use App\Services\MeasurementTypeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeasurementTypeController extends Controller
{
    protected string $modelName = 'Measurement Type';
    protected MeasurementType $model;
    protected MeasurementTypeRepo $repo;

    public function __construct(
        MeasurementType $model,
        MeasurementTypeRepo $repo,
        private readonly MeasurementTypeService $service,
    ) {
        $this->model = $model;
        $this->repo  = $repo;
    }

    public function index(Request $request): JsonResponse
    {
        $types = $this->repo->getData($request);

        if ($request->boolean('all')) {
            return response()->json(['data' => $types]);
        }

        // Shape matches UserController@index: the raw paginator under
        // "record", read by ServerDataTable as res.data.record.
        return response()->json([
            'record'  => $types,
            'message' => "{$this->modelName}s retrieved successfully",
            'status'  => true,
        ]);
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
