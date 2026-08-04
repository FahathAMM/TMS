<?php

namespace App\Repositories\Contracts;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ProductRepositoryInterface
{
    public function all(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    public function findById(int $id): ?Product;

    public function findBySlug(string $slug): ?Product;

    public function create(array $data): Product;

    public function update(int $id, array $data): Product;

    public function delete(int $id): bool;

    public function findWithDetails(int $id): ?Product;

    public function search(string $term, array $filters = []): LengthAwarePaginator;

    public function getFeatured(int $limit = 10): Collection;

    public function getByCategory(int $categoryId, bool $includeDescendants = true): LengthAwarePaginator;
}
