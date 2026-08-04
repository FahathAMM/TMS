<?php

namespace App\Http\Requests\MeasurementType;

use Illuminate\Foundation\Http\FormRequest;

class StoreMeasurementTypeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'     => 'required|string|max:100',
            'category' => 'required|string|max:100',
            'unit'     => 'nullable|string|max:10',
        ];
    }
}
