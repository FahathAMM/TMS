<?php

namespace App\Http\Requests\AlterationType;

use Illuminate\Foundation\Http\FormRequest;

class StoreAlterationTypeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'      => 'required|string|max:100',
            'price'     => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ];
    }
}
