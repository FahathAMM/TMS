<?php

namespace App\Http\Requests\Attribute;

use App\Enums\AttributeType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAttributeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $attributeId = $this->route('attribute')?->id;

        return [
            'name'          => ['sometimes', 'string', 'max:100', Rule::unique('attributes', 'name')->ignore($attributeId)],
            'type'          => ['sometimes', Rule::enum(AttributeType::class)],
            'is_required'   => 'boolean',
            'is_filterable' => 'boolean',
            'sort_order'    => 'nullable|integer|min:0',
        ];
    }
}
