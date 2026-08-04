<?php

namespace App\Http\Requests\CustomerMeasurement;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerMeasurementsRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'measurements'                        => 'required|array|min:1',
            'measurements.*.measurement_type_id'  => 'required|exists:measurement_types,id',
            'measurements.*.value'                 => 'required|numeric|min:0|max:9999.99',
        ];
    }
}
