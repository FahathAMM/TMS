<?php

namespace App\Http\Requests\AlterationOrder;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'alteration_type_id' => 'nullable|exists:alteration_types,id',
            'description'        => 'nullable|string|max:255',
            'price'              => 'nullable|numeric|min:0',
            'quantity'           => 'nullable|integer|min:1',
            'notes'              => 'nullable|string',
        ];
    }
}
