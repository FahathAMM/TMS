<?php

namespace App\Services;

use App\Models\MeasurementField;
use App\Models\MeasurementType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MeasurementTypeService
{
    /**
     * Create a measurement type together with its numbered fields.
     *
     * $data = [
     *   'name', 'description'?, 'is_active'?,
     *   'fields' => [['number', 'name', 'key'?, 'unit'?, 'required'?, 'sort_order'?], ...],
     * ]
     */
    public function create(array $data): MeasurementType
    {
        return DB::transaction(function () use ($data) {
            /** @var MeasurementType $type */
            $type = MeasurementType::create([
                'name'        => $data['name'],
                'description' => $data['description'] ?? null,
                'is_active'   => $data['is_active'] ?? true,
            ]);

            foreach ($data['fields'] ?? [] as $field) {
                $this->createField($type, $field);
            }

            return $type->load('fields');
        });
    }

    /**
     * Update a measurement type's attributes and reconcile its fields against
     * the submitted list: fields with an id are updated, fields without an id
     * are created, and existing fields missing from the payload are deleted.
     * This gives the admin add/edit/delete/reorder in a single save.
     */
    public function update(MeasurementType $type, array $data): MeasurementType
    {
        return DB::transaction(function () use ($type, $data) {
            $type->update([
                'name'        => $data['name'],
                'description' => $data['description'] ?? null,
                'is_active'   => $data['is_active'] ?? $type->is_active,
            ]);

            $incoming = collect($data['fields'] ?? []);
            $keepIds  = $incoming->pluck('id')->filter()->all();

            $type->fields()->whereNotIn('id', $keepIds)->delete();

            foreach ($incoming as $field) {
                if (!empty($field['id'])) {
                    /** @var MeasurementField $existing */
                    $existing = $type->fields()->whereKey($field['id'])->firstOrFail();
                    $existing->update($this->fieldAttributes($field));
                } else {
                    $this->createField($type, $field);
                }
            }

            return $type->fresh('fields');
        });
    }

    public function uploadImage(MeasurementType $type, UploadedFile $file): MeasurementType
    {
        if ($type->image) {
            Storage::disk('public')->delete($type->image);
        }

        $type->update(['image' => $file->store('measurement-types', 'public')]);

        return $type;
    }

    private function createField(MeasurementType $type, array $field): MeasurementField
    {
        return $type->fields()->create($this->fieldAttributes($field));
    }

    private function fieldAttributes(array $field): array
    {
        return [
            'number'     => $field['number'],
            'name'       => $field['name'],
            'key'        => $field['key'] ?? Str::slug($field['name'], '_'),
            'unit'       => $field['unit'] ?? 'inches',
            'required'   => $field['required'] ?? true,
            'sort_order' => $field['sort_order'] ?? 0,
            'is_active'  => $field['is_active'] ?? true,
        ];
    }
}
