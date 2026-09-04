<?php

namespace App\Http\Controllers\Api\Tailoring;

use App\Http\Controllers\Controller;
use App\Http\Requests\GarmentPrice\StoreGarmentPriceRequest;
use App\Http\Requests\GarmentPrice\UpdateGarmentPriceRequest;
use App\Http\Resources\GarmentPriceResource;
use App\Models\Tailoring\GarmentPrice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GarmentPriceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = GarmentPrice::query()->orderBy('garment_type');

        if ($search = $request->get('search')) {
            $query->where('garment_type', 'like', "%{$search}%");
        }

        if ($request->boolean('all')) {
            return response()->json(['data' => GarmentPriceResource::collection($query->where('is_active', true)->get())]);
        }

        $prices = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => GarmentPriceResource::collection($prices->items()),
            'meta' => [
                'current_page' => $prices->currentPage(),
                'last_page'    => $prices->lastPage(),
                'per_page'     => $prices->perPage(),
                'total'        => $prices->total(),
            ],
        ]);
    }

    public function store(StoreGarmentPriceRequest $request): JsonResponse
    {
        $price = GarmentPrice::create($request->validated());

        return response()->json([
            'message' => 'Garment price added successfully',
            'data'    => new GarmentPriceResource($price),
        ], 201);
    }

    public function update(UpdateGarmentPriceRequest $request, GarmentPrice $garmentPrice): JsonResponse
    {
        $garmentPrice->update($request->validated());

        return response()->json([
            'message' => 'Garment price updated successfully',
            'data'    => new GarmentPriceResource($garmentPrice),
        ]);
    }

    public function destroy(GarmentPrice $garmentPrice): JsonResponse
    {
        $garmentPrice->delete();

        return response()->json(['message' => 'Garment price deleted successfully']);
    }
}
