<?php

namespace App\Http\Requests\AlterationOrder;

use Illuminate\Foundation\Http\FormRequest;

class StoreGarmentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'garment_type'                     => 'required|string|max:100',
            'description'                      => 'nullable|string|max:255',
            'quantity'                          => 'nullable|integer|min:1',
            'measurements_required'             => 'nullable|boolean',
            'notes'                             => 'nullable|string',

            'tasks'                             => 'required|array|min:1',
            'tasks.*.alteration_type_id'        => 'nullable|exists:alteration_types,id',
            'tasks.*.description'               => 'nullable|string|max:255',
            'tasks.*.price'                     => 'nullable|numeric|min:0',
            'tasks.*.quantity'                  => 'nullable|integer|min:1',
            'tasks.*.notes'                     => 'nullable|string',

            'measurements'                                 => 'nullable|array',
            'measurements.*.measurement_field_id'          => 'required_with:measurements|exists:measurement_fields,id',
            'measurements.*.current_value'                 => 'nullable|numeric',
            'measurements.*.target_value'                  => 'nullable|numeric',
        ];
    }
}
