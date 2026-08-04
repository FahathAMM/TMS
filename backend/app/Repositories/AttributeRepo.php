<?php

namespace App\Repositories;

use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AttributeRepo
{
    public function getData(Request $request): Collection
    {
        $query = Attribute::with('values');

        if ($request->boolean('filterable')) {
            $query->where('is_filterable', true);
        }

        if ($request->filled('search')) {
            $query->where('name', 'LIKE', '%' . $request->search . '%');
        }

        return $query->ordered()->get();
    }

    public function store(array $data): Attribute
    {
        $data['slug'] = Str::slug($data['name']);
        $values       = $data['values'] ?? [];
        unset($data['values']);

        $attribute = Attribute::create($data);

        foreach ($values as $index => $valueData) {
            $attribute->values()->create([
                'value'      => $valueData['value'],
                'label'      => $valueData['label'] ?? $valueData['value'],
                'color_code' => $valueData['color_code'] ?? null,
                'sort_order' => $valueData['sort_order'] ?? $index,
            ]);
        }

        return $attribute->load('values');
    }

    public function update(Attribute $attribute, array $data): Attribute
    {
        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $attribute->update($data);

        return $attribute->fresh('values');
    }

    public function destroy(Attribute $attribute): void
    {
        $attribute->delete();
    }

    public function storeValue(Attribute $attribute, array $data): AttributeValue
    {
        return $attribute->values()->create([
            'value'      => $data['value'],
            'label'      => $data['label'] ?? $data['value'],
            'color_code' => $data['color_code'] ?? null,
            'sort_order' => $data['sort_order'] ?? $attribute->values()->count(),
        ]);
    }

    public function updateValue(AttributeValue $value, array $data): AttributeValue
    {
        $value->update($data);

        return $value->fresh();
    }

    public function destroyValue(AttributeValue $value): void
    {
        $value->delete();
    }
}
