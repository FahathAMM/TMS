<?php

namespace App\Http\Requests\MeasurementType;

use Illuminate\Foundation\Http\FormRequest;

class StoreMeasurementTypeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'                    => 'required|string|max:100',
            'description'             => 'nullable|string|max:1000',
            'is_active'               => 'nullable|boolean',

            'fields'                  => 'required|array|min:1',
            'fields.*.number'         => 'required|integer|min:1|distinct',
            'fields.*.name'           => 'required|string|max:100',
            'fields.*.unit'           => 'nullable|string|max:10',
            'fields.*.required'       => 'nullable|boolean',
            'fields.*.sort_order'     => 'nullable|integer|min:0',
        ];
    }
}
