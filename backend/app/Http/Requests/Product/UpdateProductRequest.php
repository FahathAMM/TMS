<?php

namespace App\Http\Requests\Product;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('product')->id;

        return [
            // Core
            'name'               => 'sometimes|string|max:255',
            'sku'                => ['nullable', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($id)],
            'barcode'            => ['nullable', 'string', 'max:100', Rule::unique('products', 'barcode')->ignore($id)],
            'type'               => ['nullable', Rule::enum(ProductType::class)],
            'status'             => ['nullable', Rule::enum(ProductStatus::class)],
            'category_id'        => 'sometimes|exists:categories,id',
            'brand_id'           => 'nullable|exists:brands,id',

            // Descriptions
            'description'        => 'nullable|string',
            'short_description'  => 'nullable|string|max:500',

            // Pricing
            'cost_price'         => 'sometimes|numeric|min:0',
            'selling_price'      => 'sometimes|numeric|min:0',
            'compare_price'      => 'nullable|numeric|min:0',

            // Inventory
            'stock_quantity'     => 'sometimes|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'unit_of_measure'    => 'nullable|string|max:20',

            // Physical
            'image'              => 'sometimes|nullable|image|max:5120',
            'weight'             => 'nullable|numeric|min:0',
            'weight_unit'        => 'nullable|string|in:kg,g,lb,oz',
            'length'             => 'nullable|numeric|min:0',
            'width'              => 'nullable|numeric|min:0',
            'height'             => 'nullable|numeric|min:0',

            // Flags
            'is_active'          => 'boolean',
            'is_featured'        => 'boolean',
            'is_best_seller'     => 'boolean',
            'is_digital'         => 'boolean',
            'sort_order'         => 'nullable|integer|min:0',
            'published_at'       => 'nullable|date',

        ];
    }
}
