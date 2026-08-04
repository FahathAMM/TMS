<?php

namespace App\Http\Requests\Attribute;

use App\Enums\AttributeType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttributeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                    => 'required|string|max:100|unique:attributes,name',
            'type'                    => ['required', Rule::enum(AttributeType::class)],
            'is_required'             => 'boolean',
            'is_filterable'           => 'boolean',
            'sort_order'              => 'nullable|integer|min:0',
            'values'                  => 'nullable|array',
            'values.*.value'          => 'required_with:values|string|max:100',
            'values.*.label'          => 'nullable|string|max:100',
            'values.*.color_code'     => 'nullable|string|max:20',
            'values.*.sort_order'     => 'nullable|integer|min:0',
        ];
    }
}
