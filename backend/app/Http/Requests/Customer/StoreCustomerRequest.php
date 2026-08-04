<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'mobile' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'email' => 'nullable|email|unique:customers,email',
            'is_active' => 'boolean',
        ];
    }
}
