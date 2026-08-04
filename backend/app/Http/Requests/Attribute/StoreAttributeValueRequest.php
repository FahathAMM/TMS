<?php

namespace App\Http\Requests\Attribute;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttributeValueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'value'      => 'required|string|max:100',
            'label'      => 'nullable|string|max:100',
            'color_code' => 'nullable|string|max:20',
            'sort_order' => 'nullable|integer|min:0',
        ];
    }
}
