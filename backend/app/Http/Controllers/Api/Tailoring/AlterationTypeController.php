<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AlterationType\StoreAlterationTypeRequest;
use App\Http\Requests\AlterationType\UpdateAlterationTypeRequest;
use App\Http\Resources\AlterationTypeResource;
use App\Models\AlterationType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlterationTypeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = AlterationType::query()->orderBy('name');

        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->boolean('all')) {
            return response()->json(['data' => AlterationTypeResource::collection($query->where('is_active', true)->get())]);
        }

        $types = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => AlterationTypeResource::collection($types->items()),
            'meta' => [
                'current_page' => $types->currentPage(),
                'last_page'    => $types->lastPage(),
                'per_page'     => $types->perPage(),
                'total'        => $types->total(),
            ],
        ]);
    }

    public function store(StoreAlterationTypeRequest $request): JsonResponse
    {
        $type = AlterationType::create($request->validated());

        return response()->json([
            'message' => 'Alteration type added successfully',
            'data'    => new AlterationTypeResource($type),
        ], 201);
    }

    public function update(UpdateAlterationTypeRequest $request, AlterationType $alterationType): JsonResponse
    {
        $alterationType->update($request->validated());

        return response()->json([
            'message' => 'Alteration type updated successfully',
            'data'    => new AlterationTypeResource($alterationType),
        ]);
    }

    public function destroy(AlterationType $alterationType): JsonResponse
    {
        $alterationType->delete();

        return response()->json(['message' => 'Alteration type deleted successfully']);
    }
}
