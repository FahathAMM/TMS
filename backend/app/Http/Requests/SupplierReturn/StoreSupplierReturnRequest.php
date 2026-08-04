<?php

namespace App\Http\Requests\SupplierReturn;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierReturnRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'supplier_id'          => 'required|exists:suppliers,id',
            'purchase_id'          => 'nullable|exists:purchases,id',
            'return_date'          => 'required|date',
            'reason'               => 'nullable|string',
            'notes'                => 'nullable|string',
            'items'                => 'required|array|min:1',
            'items.*.product_id'   => 'required|exists:products,id',
            'items.*.variant_id'   => 'nullable|exists:product_variants,id',
            'items.*.quantity'     => 'required|numeric|min:0.001',
            'items.*.cost_price'   => 'required|numeric|min:0',
        ];
    }
}
