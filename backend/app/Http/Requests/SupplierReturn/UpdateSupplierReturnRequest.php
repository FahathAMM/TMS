<?php

namespace App\Http\Requests\SupplierReturn;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierReturnRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'supplier_id'          => 'sometimes|required|exists:suppliers,id',
            'purchase_id'          => 'nullable|exists:purchases,id',
            'return_date'          => 'sometimes|required|date',
            'reason'               => 'nullable|string',
            'notes'                => 'nullable|string',
            'items'                => 'nullable|array',
            'items.*.product_id'   => 'required_with:items|exists:products,id',
            'items.*.variant_id'   => 'nullable|exists:product_variants,id',
            'items.*.quantity'     => 'required_with:items|numeric|min:0.001',
            'items.*.cost_price'   => 'required_with:items|numeric|min:0',
        ];
    }
}
