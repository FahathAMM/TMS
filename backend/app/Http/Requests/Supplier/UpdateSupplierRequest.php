<?php

namespace App\Http\Requests\Supplier;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('supplier')?->id;

        return [
            'name'        => 'sometimes|required|string|max:191',
            'company'     => 'nullable|string|max:191',
            'phone'       => 'nullable|string|max:30',
            'email'       => "nullable|email|unique:suppliers,email,{$id}",
            'address'     => 'nullable|string',
            'city'        => 'nullable|string|max:100',
            'tax_number'  => 'nullable|string|max:100',
            'status'      => 'nullable|in:active,inactive,blacklisted',
            'notes'       => 'nullable|string',
        ];
    }
}
