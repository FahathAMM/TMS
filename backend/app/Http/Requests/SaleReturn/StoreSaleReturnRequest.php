<?php

namespace App\Http\Requests\SaleReturn;

use Illuminate\Foundation\Http\FormRequest;

class StoreSaleReturnRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'sale_id'                  => 'nullable|exists:sales,id',
            'customer_id'              => 'nullable|exists:customers,id',
            'return_date'              => 'required|date',
            'refund_type'              => 'required|in:cash,wallet,store_credit,replace,none',
            'reason'                   => 'nullable|string|max:255',
            'notes'                    => 'nullable|string',
            'items'                    => 'required|array|min:1',
            'items.*.product_id'       => 'required|exists:products,id',
            'items.*.variant_id'       => 'nullable|exists:product_variants,id',
            'items.*.quantity'         => 'required|numeric|min:0.001',
            'items.*.unit_price'       => 'required|numeric|min:0',
            'items.*.return_reason'    => 'nullable|string|max:255',
        ];
    }
}
