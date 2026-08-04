<?php

namespace App\Http\Requests\Purchase;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'supplier_id'              => 'sometimes|required|exists:suppliers,id',
            'purchase_date'            => 'sometimes|required|date',
            'expected_delivery'        => 'nullable|date',
            'status'                   => 'nullable|in:draft,ordered',
            'shipping_cost'            => 'nullable|numeric|min:0',
            'notes'                    => 'nullable|string',
            'items'                    => 'nullable|array',
            'items.*.product_id'       => 'required_with:items|exists:products,id',
            'items.*.variant_id'       => 'nullable|exists:product_variants,id',
            'items.*.quantity_ordered' => 'required_with:items|numeric|min:0.001',
            'items.*.cost_price'       => 'required_with:items|numeric|min:0',
            'items.*.tax_rate'         => 'nullable|numeric|min:0|max:100',
            'items.*.discount_amount'  => 'nullable|numeric|min:0',
        ];
    }
}
