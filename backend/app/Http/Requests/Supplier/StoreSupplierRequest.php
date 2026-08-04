<?php

namespace App\Http\Requests\Supplier;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'                   => 'required|string|max:191',
            'company'                => 'nullable|string|max:191',
            'phone'                  => 'nullable|string|max:30',
            'email'                  => 'nullable|email|unique:suppliers,email',
            'address'                => 'nullable|string',
            'city'                   => 'nullable|string|max:100',
            'tax_number'             => 'nullable|string|max:100',
            'opening_balance'        => 'nullable|numeric|min:0',
            'status'                 => 'nullable|in:active,inactive,blacklisted',
            'notes'                  => 'nullable|string',
            'contacts'               => 'nullable|array',
            'contacts.*.name'        => 'required_with:contacts|string|max:191',
            'contacts.*.phone'       => 'nullable|string|max:30',
            'contacts.*.email'       => 'nullable|email',
            'contacts.*.designation' => 'nullable|string|max:100',
            'contacts.*.is_primary'  => 'nullable|boolean',
        ];
    }
}
