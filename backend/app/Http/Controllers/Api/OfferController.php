<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use App\Services\OfferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OfferController extends Controller
{
    public function __construct(private readonly OfferService $offerService) {}

    public function index(Request $request): JsonResponse
    {
        $query = Offer::with('products:id,name');

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', (bool) $request->is_active);
        }

        $offers = $query->latest()->paginate($request->input('per_page', 15));

        return response()->json($offers);
    }

    public function active(): JsonResponse
    {
        $offers = $this->offerService->getActiveOffers();

        return response()->json(['data' => $offers]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'       => 'required|string|max:150',
            'type'        => ['required', Rule::in(['percentage', 'fixed'])],
            'value'       => 'required|numeric|min:0.01',
            'start_date'  => 'nullable|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
            'is_active'   => 'boolean',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'integer|exists:products,id',
        ]);

        $offer = Offer::create($data);

        if (! empty($data['product_ids'])) {
            $offer->products()->sync($data['product_ids']);
        }

        return response()->json(['data' => $offer->load('products:id,name')], 201);
    }

    public function show(Offer $offer): JsonResponse
    {
        return response()->json(['data' => $offer->load('products:id,name')]);
    }

    public function update(Request $request, Offer $offer): JsonResponse
    {
        $data = $request->validate([
            'title'       => 'sometimes|required|string|max:150',
            'type'        => ['sometimes', 'required', Rule::in(['percentage', 'fixed'])],
            'value'       => 'sometimes|required|numeric|min:0.01',
            'start_date'  => 'nullable|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
            'is_active'   => 'boolean',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'integer|exists:products,id',
        ]);

        $offer->update($data);

        if (array_key_exists('product_ids', $data)) {
            $offer->products()->sync($data['product_ids'] ?? []);
        }

        return response()->json(['data' => $offer->load('products:id,name')]);
    }

    public function destroy(Offer $offer): JsonResponse
    {
        $offer->delete();

        return response()->json(['message' => 'Offer deleted']);
    }

    public function toggleActive(Offer $offer): JsonResponse
    {
        $offer->update(['is_active' => ! $offer->is_active]);

        return response()->json(['data' => ['is_active' => $offer->is_active]]);
    }
}
