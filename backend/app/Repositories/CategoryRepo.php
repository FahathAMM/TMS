<?php

namespace App\Repositories;

use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CategoryRepo
{
    public function getData(Request $request): LengthAwarePaginator|Collection
    {
        $query = Category::withCount('products');

        if ($request->filled('search')) {
            $query->where('name', 'LIKE', '%' . $request->search . '%');
        }

        if ($request->boolean('all')) {
            return $query->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        }

        if ($request->boolean('tree')) {
            return $this->tree($request->boolean('active_only', false));
        }

        return $query->latest()->paginate($request->input('per_page', 15));
    }

    public function tree(bool $activeOnly = false): Collection
    {
        $query = Category::with(['children' => function ($q) use ($activeOnly) {
            if ($activeOnly) {
                $q->where('is_active', true);
            }
            $q->withCount('products')->orderBy('sort_order');
        }])->withCount('products')->whereNull('parent_id');

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->orderBy('sort_order')->get();
    }

    public function store(array $data): Category
    {
        $data['slug'] = Str::slug($data['name']);

        if (isset($data['image']) && $data['image'] instanceof \Illuminate\Http\UploadedFile) {
            $data['image'] = $data['image']->store('categories', 'public');
        }

        return Category::create($data);
    }

    public function update(Category $category, array $data): Category
    {
        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        if (isset($data['image']) && $data['image'] instanceof \Illuminate\Http\UploadedFile) {
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }
            $data['image'] = $data['image']->store('categories', 'public');
        }

        $category->update($data);

        return $category->fresh();
    }

    public function destroy(Category $category): void
    {
        if ($category->products()->exists()) {
            throw new \RuntimeException('Cannot delete a category that has associated products.');
        }

        if ($category->children()->exists()) {
            throw new \RuntimeException('Cannot delete a category that has sub-categories.');
        }

        if ($category->image) {
            Storage::disk('public')->delete($category->image);
        }

        $category->delete();
    }

    public function reorder(array $order): void
    {
        foreach ($order as $item) {
            Category::where('id', $item['id'])->update([
                'sort_order' => $item['sort_order'],
                'parent_id'  => $item['parent_id'] ?? null,
            ]);
        }
    }
}
