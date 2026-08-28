<?php

namespace App\Http\Requests\MeasurementType;

use Illuminate\Foundation\Http\FormRequest;

class UploadMeasurementTypeImageRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'image' => 'required|image|max:5120',
        ];
    }
}
