<?php

namespace App\Repositories\Eloquent;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Repositories\Contracts\AttributeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class AttributeRepository implements AttributeRepositoryInterface
{
    public function all(): Collection
    {
        return Attribute::with('values')->ordered()->get();
    }

    public function filterable(): Collection
    {
        return Attribute::with('values')->filterable()->ordered()->get();
    }

    public function findById(int $id): ?Attribute
    {
        return Attribute::with('values')->find($id);
    }

    public function create(array $data): Attribute
    {
        return Attribute::create($data);
    }

    public function update(int $id, array $data): Attribute
    {
        $attribute = Attribute::findOrFail($id);
        $attribute->update($data);
        return $attribute->fresh('values');
    }

    public function delete(int $id): bool
    {
        $attribute = Attribute::findOrFail($id);
        return (bool) $attribute->delete();
    }

    public function addValue(int $attributeId, array $data): AttributeValue
    {
        $attribute = Attribute::findOrFail($attributeId);

        return $attribute->values()->create($data);
    }

    public function updateValue(int $valueId, array $data): AttributeValue
    {
        $value = AttributeValue::findOrFail($valueId);
        $value->update($data);
        return $value->fresh();
    }

    public function deleteValue(int $valueId): bool
    {
        $value = AttributeValue::findOrFail($valueId);
        return (bool) $value->delete();
    }
}
