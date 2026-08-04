<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('customer')->id;

        return [
            'name' => 'sometimes|string|max:255',
            'mobile' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'email' => "nullable|email|unique:customers,email,{$id}",
            'is_active' => 'boolean',
        ];
    }
}
