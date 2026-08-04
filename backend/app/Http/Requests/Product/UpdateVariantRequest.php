<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $variantId = $this->route('variant')?->id;

        return [
            'sku'                    => ['nullable', 'string', 'max:100', Rule::unique('product_variants', 'sku')->ignore($variantId)],
            'price'                  => 'sometimes|numeric|min:0',
            'compare_price'          => 'nullable|numeric|min:0',
            'cost_price'             => 'nullable|numeric|min:0',
            'stock_quantity'         => 'sometimes|integer|min:0',
            'low_stock_threshold'    => 'nullable|integer|min:0',
            'weight'                 => 'nullable|numeric|min:0',
            'is_active'              => 'boolean',
            'sort_order'             => 'nullable|integer|min:0',
            'attribute_value_ids'    => 'nullable|array',
            'attribute_value_ids.*'  => 'integer|exists:attribute_values,id',
        ];
    }
}
