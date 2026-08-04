<?php

namespace App\Repositories;

use App\Models\Tag;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TagRepo
{
    public function getData(Request $request): LengthAwarePaginator|Collection
    {
        $query = Tag::withCount('products');

        if ($request->filled('search')) {
            $query->where('name', 'LIKE', '%' . $request->search . '%');
        }

        if ($request->boolean('all')) {
            return $query->orderBy('name')->get();
        }

        return $query->orderBy('name')->paginate($request->input('per_page', 50));
    }

    public function store(array $data): Tag
    {
        $data['slug'] = Str::slug($data['name']);

        return Tag::create($data);
    }

    public function update(Tag $tag, array $data): Tag
    {
        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $tag->update($data);

        return $tag->fresh();
    }

    public function destroy(Tag $tag): void
    {
        $tag->products()->detach();
        $tag->delete();
    }
}
