<?php

namespace App\Repositories;

use App\Models\Administration\StoreSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StoreSettingRepo
{
    public const CACHE_KEY = 'store_settings:all';
    public const CACHE_TTL = 86400; // 24 hours

    public function __construct(private StoreSetting $model) {}

    public function allCached(): Collection
    {
        return Cache::remember(
            self::CACHE_KEY,
            self::CACHE_TTL,
            fn () => $this->model->orderBy('group')->orderBy('sort_order')->get()
        );
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->allCached()->firstWhere('key', $key)?->value ?? $default;
    }

    public function getByGroup(string $group): Collection
    {
        return $this->allCached()->where('group', $group)->values();
    }

    public function getGrouped(): array
    {
        return $this->allCached()->groupBy('group')->toArray();
    }

    public function getPublicFlat(): array
    {
        return $this->allCached()->pluck('value', 'key')->toArray();
    }

    public function updateGroup(string $group, array $data): void
    {
        DB::transaction(function () use ($group, $data) {
            foreach ($data as $key => $value) {
                $this->model
                    ->where('key', $key)
                    ->where('group', $group)
                    ->update(['value' => is_array($value) ? json_encode($value) : $value]);
            }
        });

        Cache::forget(self::CACHE_KEY);
    }

    public function uploadMedia(string $key, UploadedFile $file): string
    {
        // Remove old file from storage if it's a local path
        $old = $this->get($key);
        if ($old && str_contains($old, '/storage/settings/')) {
            $oldPath = ltrim(parse_url($old, PHP_URL_PATH), '/');
            $oldPath = str_replace('storage/', '', $oldPath);
            Storage::disk('public')->delete($oldPath);
        }

        $path = $file->store('settings', 'public');
        $url  = Storage::url($path);

        $this->model->where('key', $key)->update(['value' => $url]);
        Cache::forget(self::CACHE_KEY);

        return $url;
    }

    public function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
