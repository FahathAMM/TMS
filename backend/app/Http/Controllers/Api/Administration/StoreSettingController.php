<?php

namespace App\Http\Controllers\Api\Administration;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateStoreSettingRequest;
use App\Repositories\StoreSettingRepo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StoreSettingController extends Controller
{
    public function __construct(private StoreSettingRepo $repo) {}

    public function index(): JsonResponse
    {
        $grouped = $this->repo->allCached()->groupBy('group');

        return response()->json(['data' => $grouped]);
    }

    public function update(string $group, UpdateStoreSettingRequest $request): JsonResponse
    {
        $this->repo->updateGroup($group, $request->all());

        return response()->json([
            'data'    => $this->repo->getByGroup($group),
            'message' => 'Settings saved',
        ]);
    }

    public function uploadMedia(Request $request): JsonResponse
    {
        $request->validate([
            'key'  => 'required|string|exists:store_settings,key',
            'file' => 'required|file|mimes:jpeg,jpg,png,gif,svg,webp,ico|max:5120',
        ]);

        $url = $this->repo->uploadMedia($request->input('key'), $request->file('file'));

        return response()->json(['data' => ['url' => $url], 'message' => 'File uploaded']);
    }

    public function uploadFile(Request $request): JsonResponse
    {
        $request->validate([
            'file'   => 'required|file|mimes:jpeg,jpg,png,gif,webp|max:5120',
            'folder' => 'nullable|string|max:50|regex:/^[a-z0-9_-]+$/',
        ]);

        $folder = $request->input('folder', 'general');
        $path   = $request->file('file')->store("settings/{$folder}", 'public');
        $url    = Storage::url($path);

        return response()->json(['data' => ['url' => $url], 'message' => 'File uploaded']);
    }
}
