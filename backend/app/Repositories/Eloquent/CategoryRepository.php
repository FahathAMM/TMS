<?php

namespace App\Repositories\Eloquent;

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

class CategoryRepository implements CategoryRepositoryInterface
{
    public function all(bool $onlyActive = true): Collection
    {
        $query = Category::withDepth()->orderBy('depth')->orderBy('sort_order');

        if ($onlyActive) {
            $query->active();
        }

        return $query->get();
    }

    public function tree(bool $onlyActive = true): Collection
    {
        $query = Category::with(['children' => function ($q) use ($onlyActive): void {
            if ($onlyActive) {
                $q->where('is_active', true);
            }
            $q->orderBy('sort_order');
        }])->roots()->orderBy('sort_order');

        if ($onlyActive) {
            $query->active();
        }

        return $query->get();
    }

    public function findById(int $id): ?Category
    {
        return Category::find($id);
    }

    public function findBySlug(string $slug): ?Category
    {
        return Category::where('slug', $slug)->first();
    }

    public function create(array $data): Category
    {
        return Category::create($data);
    }

    public function update(int $id, array $data): Category
    {
        $category = Category::findOrFail($id);

        $oldParentId = $category->parent_id;
        $category->update($data);

        // If parent changed, update descendants' paths
        if (array_key_exists('parent_id', $data) && $data['parent_id'] !== $oldParentId) {
            $this->updateDescendantPaths($category);
        }

        return $category->fresh();
    }

    public function delete(int $id): bool
    {
        $category = Category::findOrFail($id);

        if ($category->hasChildren()) {
            throw new RuntimeException('Cannot delete a category that has child categories.');
        }

        if ($category->products()->exists()) {
            throw new RuntimeException('Cannot delete a category that has associated products.');
        }

        return (bool) $category->delete();
    }

    public function roots(): Collection
    {
        return Category::roots()->orderBy('sort_order')->get();
    }

    public function children(int $parentId): Collection
    {
        return Category::where('parent_id', $parentId)->orderBy('sort_order')->get();
    }

    public function descendants(int $categoryId): Collection
    {
        $category = Category::findOrFail($categoryId);
        return $category->getDescendants();
    }

    public function reorder(array $order): void
    {
        foreach ($order as $item) {
            if (isset($item['id'], $item['sort_order'])) {
                Category::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
            }
        }
    }

    // ─── Internal helpers ─────────────────────────────────────────────────────

    protected function updateDescendantPaths(Category $category): void
    {
        $descendants = $category->getDescendants();

        foreach ($descendants as $descendant) {
            // Re-compute path based on updated parent chain
            $descendant->refresh();
            $descendant->save(); // triggers boot computePathAndDepth via update
        }
    }
}
