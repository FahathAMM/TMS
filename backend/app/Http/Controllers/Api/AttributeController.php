<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attribute\StoreAttributeRequest;
use App\Http\Requests\Attribute\StoreAttributeValueRequest;
use App\Http\Requests\Attribute\UpdateAttributeRequest;
use App\Http\Requests\Attribute\UpdateAttributeValueRequest;
use App\Http\Resources\AttributeResource;
use App\Http\Resources\AttributeValueResource;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Repositories\AttributeRepo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttributeController extends Controller
{
    protected string $modelName = 'Attribute';
    protected string $routeName = 'attributes';
    protected bool $isDestroyingAllowed;
    protected Attribute $model;
    protected AttributeRepo $repo;

    public function __construct(Attribute $model, AttributeRepo $repo)
    {
        $this->model               = $model;
        $this->repo                = $repo;
        $this->isDestroyingAllowed = true;
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => AttributeResource::collection($this->repo->getData($request)),
        ]);
    }

    public function store(StoreAttributeRequest $request): JsonResponse
    {
        $attribute = $this->repo->store($request->validated());

        return response()->json([
            'message' => "{$this->modelName} created successfully",
            'data'    => new AttributeResource($attribute),
        ], 201);
    }

    public function show(Attribute $attribute): JsonResponse
    {
        return response()->json([
            'data' => new AttributeResource($attribute->load('values')),
        ]);
    }

    public function update(UpdateAttributeRequest $request, Attribute $attribute): JsonResponse
    {
        $attribute = $this->repo->update($attribute, $request->validated());

        return response()->json([
            'message' => "{$this->modelName} updated successfully",
            'data'    => new AttributeResource($attribute),
        ]);
    }

    public function destroy(Attribute $attribute): JsonResponse
    {
        $this->repo->destroy($attribute);

        return response()->json(['message' => "{$this->modelName} deleted successfully"]);
    }

    // ─── Values ───────────────────────────────────────────────────────────────

    public function storeValue(StoreAttributeValueRequest $request, Attribute $attribute): JsonResponse
    {
        $value = $this->repo->storeValue($attribute, $request->validated());

        return response()->json([
            'message' => 'Attribute value added',
            'data'    => new AttributeValueResource($value),
        ], 201);
    }

    public function updateValue(UpdateAttributeValueRequest $request, Attribute $attribute, AttributeValue $value): JsonResponse
    {
        abort_if($value->attribute_id !== $attribute->id, 404);

        $value = $this->repo->updateValue($value, $request->validated());

        return response()->json([
            'message' => 'Attribute value updated',
            'data'    => new AttributeValueResource($value),
        ]);
    }

    public function destroyValue(Attribute $attribute, AttributeValue $value): JsonResponse
    {
        abort_if($value->attribute_id !== $attribute->id, 404);

        $this->repo->destroyValue($value);

        return response()->json(['message' => 'Attribute value deleted']);
    }
}
