<?php

namespace App\Repositories\Contracts;

use App\Models\Brand;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface BrandRepositoryInterface
{
    public function all(array $filters = []): Collection;

    public function paginated(int $perPage = 20): LengthAwarePaginator;

    public function findById(int $id): ?Brand;

    public function create(array $data): Brand;

    public function update(int $id, array $data): Brand;

    public function delete(int $id): bool;

    public function withProductCount(): Collection;
}
