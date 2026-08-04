<?php

namespace App\Http\Requests\Supplier;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierContactRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'        => 'required|string|max:191',
            'phone'       => 'nullable|string|max:30',
            'email'       => 'nullable|email',
            'designation' => 'nullable|string|max:100',
            'is_primary'  => 'nullable|boolean',
        ];
    }
}
