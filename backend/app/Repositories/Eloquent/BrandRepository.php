<?php

namespace App\Repositories\Eloquent;

use App\Models\Brand;
use App\Repositories\Contracts\BrandRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class BrandRepository implements BrandRepositoryInterface
{
    public function all(array $filters = []): Collection
    {
        $query = Brand::query();

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        if (! empty($filters['search'])) {
            $query->where('name', 'LIKE', '%' . $filters['search'] . '%');
        }

        return $query->orderBy('name')->get();
    }

    public function paginated(int $perPage = 20): LengthAwarePaginator
    {
        return Brand::orderBy('name')->paginate($perPage);
    }

    public function findById(int $id): ?Brand
    {
        return Brand::find($id);
    }

    public function create(array $data): Brand
    {
        return Brand::create($data);
    }

    public function update(int $id, array $data): Brand
    {
        $brand = Brand::findOrFail($id);
        $brand->update($data);
        return $brand->fresh();
    }

    public function delete(int $id): bool
    {
        $brand = Brand::findOrFail($id);
        return (bool) $brand->delete();
    }

    public function withProductCount(): Collection
    {
        return Brand::withCount('products')->orderBy('name')->get();
    }
}
