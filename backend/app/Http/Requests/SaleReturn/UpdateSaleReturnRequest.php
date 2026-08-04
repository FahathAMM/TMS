<?php

namespace App\Http\Requests\SaleReturn;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSaleReturnRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'sale_id'                  => 'nullable|exists:sales,id',
            'customer_id'              => 'nullable|exists:customers,id',
            'return_date'              => 'sometimes|required|date',
            'refund_type'              => 'sometimes|required|in:cash,wallet,store_credit,replace,none',
            'reason'                   => 'nullable|string|max:255',
            'notes'                    => 'nullable|string',
            'items'                    => 'nullable|array',
            'items.*.product_id'       => 'required_with:items|exists:products,id',
            'items.*.variant_id'       => 'nullable|exists:product_variants,id',
            'items.*.quantity'         => 'required_with:items|numeric|min:0.001',
            'items.*.unit_price'       => 'required_with:items|numeric|min:0',
            'items.*.return_reason'    => 'nullable|string|max:255',
        ];
    }
}
