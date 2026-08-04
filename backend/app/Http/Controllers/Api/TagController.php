<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tag\StoreTagRequest;
use App\Http\Requests\Tag\UpdateTagRequest;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use App\Repositories\TagRepo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TagController extends Controller
{
    protected string $modelName = 'Tag';
    protected string $routeName = 'tags';
    protected bool $isDestroyingAllowed;
    protected Tag $model;
    protected TagRepo $repo;

    public function __construct(Tag $model, TagRepo $repo)
    {
        $this->model               = $model;
        $this->repo                = $repo;
        $this->isDestroyingAllowed = true;
    }

    public function index(Request $request): JsonResponse
    {
        $tags = $this->repo->getData($request);

        if ($request->boolean('all')) {
            return response()->json(['data' => TagResource::collection($tags)]);
        }

        return response()->json([
            'data' => TagResource::collection($tags->items()),
            'meta' => [
                'current_page' => $tags->currentPage(),
                'last_page'    => $tags->lastPage(),
                'per_page'     => $tags->perPage(),
                'total'        => $tags->total(),
            ],
        ]);
    }

    public function store(StoreTagRequest $request): JsonResponse
    {
        $tag = $this->repo->store($request->validated());

        return response()->json([
            'message' => "{$this->modelName} created successfully",
            'data'    => new TagResource($tag),
        ], 201);
    }

    public function show(Tag $tag): JsonResponse
    {
        return response()->json([
            'data' => new TagResource($tag->loadCount('products')),
        ]);
    }

    public function update(UpdateTagRequest $request, Tag $tag): JsonResponse
    {
        $tag = $this->repo->update($tag, $request->validated());

        return response()->json([
            'message' => "{$this->modelName} updated successfully",
            'data'    => new TagResource($tag),
        ]);
    }

    public function destroy(Tag $tag): JsonResponse
    {
        $this->repo->destroy($tag);

        return response()->json(['message' => "{$this->modelName} deleted successfully"]);
    }
}
