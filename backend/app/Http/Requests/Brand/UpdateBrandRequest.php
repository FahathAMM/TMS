<?php

namespace App\Http\Requests\Brand;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBrandRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('brand')->id;

        return [
            'name' => "sometimes|string|max:255|unique:brands,name,{$id}",
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }
}
