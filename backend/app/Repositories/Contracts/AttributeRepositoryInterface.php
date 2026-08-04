<?php

namespace App\Repositories\Contracts;

use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Database\Eloquent\Collection;

interface AttributeRepositoryInterface
{
    public function all(): Collection;

    public function filterable(): Collection;

    public function findById(int $id): ?Attribute;

    public function create(array $data): Attribute;

    public function update(int $id, array $data): Attribute;

    public function delete(int $id): bool;

    public function addValue(int $attributeId, array $data): AttributeValue;

    public function updateValue(int $valueId, array $data): AttributeValue;

    public function deleteValue(int $valueId): bool;
}
