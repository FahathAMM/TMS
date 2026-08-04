<?php

namespace App\Repositories\Contracts;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

interface CategoryRepositoryInterface
{
    public function all(bool $onlyActive = true): Collection;

    public function tree(bool $onlyActive = true): Collection;

    public function findById(int $id): ?Category;

    public function findBySlug(string $slug): ?Category;

    public function create(array $data): Category;

    public function update(int $id, array $data): Category;

    public function delete(int $id): bool;

    public function roots(): Collection;

    public function children(int $parentId): Collection;

    public function descendants(int $categoryId): Collection;

    public function reorder(array $order): void;
}
