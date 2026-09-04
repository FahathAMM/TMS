<?php

namespace App\Repositories;

use App\Models\Inventory\Brand;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BrandRepo
{
    public function getData(Request $request): LengthAwarePaginator|Collection
    {
        $query = Brand::withCount('products');

        if ($request->filled('search')) {
            $query->where('name', 'LIKE', '%' . $request->search . '%');
        }

        if ($request->boolean('all')) {
            return $query->where('is_active', true)->orderBy('name')->get();
        }

        $sortField = $request->input('sort', 'name');
        $sortDir   = $request->input('dir', 'asc');

        $query->orderBy(in_array($sortField, ['name', 'created_at']) ? $sortField : 'name', $sortDir);

        return $query->paginate($request->input('per_page', 15));
    }

    public function store(array $data): Brand
    {
        $data['slug'] = Str::slug($data['name']);

        if (isset($data['logo']) && $data['logo'] instanceof \Illuminate\Http\UploadedFile) {
            $data['logo'] = $data['logo']->store('brands', 'public');
        }

        return Brand::create($data);
    }

    public function update(Brand $brand, array $data): Brand
    {
        $data['slug'] = Str::slug($data['name'] ?? $brand->name);

        if (isset($data['logo']) && $data['logo'] instanceof \Illuminate\Http\UploadedFile) {
            if ($brand->logo) {
                Storage::disk('public')->delete($brand->logo);
            }
            $data['logo'] = $data['logo']->store('brands', 'public');
        }

        $brand->update($data);

        return $brand->fresh();
    }

    public function destroy(Brand $brand): void
    {
        if ($brand->products()->exists()) {
            throw new \RuntimeException('Cannot delete a brand that has associated products.');
        }

        if ($brand->logo) {
            Storage::disk('public')->delete($brand->logo);
        }

        $brand->delete();
    }
}
