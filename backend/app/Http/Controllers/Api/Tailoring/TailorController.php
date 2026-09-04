<?php

namespace App\Http\Controllers\Api\Tailoring;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tailor\StoreTailorRequest;
use App\Http\Requests\Tailor\UpdateTailorRequest;
use App\Http\Resources\TailorResource;
use App\Models\Tailoring\Tailor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TailorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Tailor::query()->latest();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('all')) {
            return response()->json(['data' => TailorResource::collection($query->where('is_active', true)->get())]);
        }

        $tailors = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => TailorResource::collection($tailors->items()),
            'meta' => [
                'current_page' => $tailors->currentPage(),
                'last_page'    => $tailors->lastPage(),
                'per_page'     => $tailors->perPage(),
                'total'        => $tailors->total(),
            ],
        ]);
    }

    public function store(StoreTailorRequest $request): JsonResponse
    {
        $tailor = Tailor::create($request->validated());

        return response()->json([
            'message' => 'Tailor created successfully',
            'data'    => new TailorResource($tailor),
        ], 201);
    }

    public function show(Tailor $tailor): JsonResponse
    {
        return response()->json(['data' => new TailorResource($tailor)]);
    }

    public function update(UpdateTailorRequest $request, Tailor $tailor): JsonResponse
    {
        $tailor->update($request->validated());

        return response()->json([
            'message' => 'Tailor updated successfully',
            'data'    => new TailorResource($tailor),
        ]);
    }

    public function destroy(Tailor $tailor): JsonResponse
    {
        $tailor->delete();

        return response()->json(['message' => 'Tailor deleted successfully']);
    }
}
