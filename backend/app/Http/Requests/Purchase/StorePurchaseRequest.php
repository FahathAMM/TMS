<?php

namespace App\Http\Requests\Purchase;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'supplier_id'              => 'required|exists:suppliers,id',
            'purchase_date'            => 'required|date',
            'expected_delivery'        => 'nullable|date|after_or_equal:purchase_date',
            'status'                   => 'nullable|in:draft,ordered',
            'shipping_cost'            => 'nullable|numeric|min:0',
            'notes'                    => 'nullable|string',
            'items'                    => 'required|array|min:1',
            'items.*.product_id'       => 'required|exists:products,id',
            'items.*.variant_id'       => 'nullable|exists:product_variants,id',
            'items.*.quantity_ordered' => 'required|numeric|min:0.001',
            'items.*.cost_price'       => 'required|numeric|min:0',
            'items.*.tax_rate'         => 'nullable|numeric|min:0|max:100',
            'items.*.discount_amount'  => 'nullable|numeric|min:0',
        ];
    }
}
