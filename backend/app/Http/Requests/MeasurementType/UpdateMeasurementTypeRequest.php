<?php

namespace App\Http\Requests\MeasurementType;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMeasurementTypeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'     => 'sometimes|required|string|max:100',
            'category' => 'sometimes|required|string|max:100',
            'unit'     => 'nullable|string|max:10',
        ];
    }
}
