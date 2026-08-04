<?php

namespace App\Http\Requests\Product;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Core
            'name'               => 'required|string|max:255',
            'sku'                => 'nullable|string|max:100|unique:products,sku',
            'barcode'            => 'nullable|string|max:100|unique:products,barcode',
            'type'               => ['nullable', Rule::enum(ProductType::class)],
            'status'             => ['nullable', Rule::enum(ProductStatus::class)],
            'category_id'        => 'required|exists:categories,id',
            'brand_id'           => 'nullable|exists:brands,id',

            // Descriptions
            'description'        => 'nullable|string',
            'short_description'  => 'nullable|string|max:500',

            // Pricing
            'cost_price'         => 'required|numeric|min:0',
            'selling_price'      => 'required|numeric|min:0',
            'compare_price'      => 'nullable|numeric|min:0',

            // Inventory
            'stock_quantity'     => 'required|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'unit_of_measure'    => 'nullable|string|max:20',

            // Physical
            'image'              => 'nullable|image|max:5120',
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

            // Relations
            'tags'               => 'nullable|array',
            'tags.*'             => 'integer|exists:tags,id',
            'attributes'         => 'nullable|array',
            'attributes.*'       => 'integer|exists:attributes,id',

            // SEO
            'seo'                      => 'nullable|array',
            'seo.meta_title'           => 'nullable|string|max:255',
            'seo.meta_description'     => 'nullable|string|max:500',
            'seo.meta_keywords'        => 'nullable|string|max:255',
            'seo.og_title'             => 'nullable|string|max:255',
            'seo.og_description'       => 'nullable|string|max:500',
            'seo.og_image'             => 'nullable|string|max:500',
            'seo.canonical_url'        => 'nullable|url|max:500',
            'seo.schema_markup'        => 'nullable|array',

            // Specifications
            'specifications'           => 'nullable|array',
            'specifications.*.group'   => 'nullable|string|max:100',
            'specifications.*.label'   => 'required_with:specifications|string|max:255',
            'specifications.*.value'   => 'required_with:specifications|string|max:1000',
            'specifications.*.sort_order' => 'nullable|integer|min:0',

            // Variants (for variable products)
            'variants'                           => 'nullable|array',
            'variants.*.sku'                     => 'nullable|string|max:100|unique:product_variants,sku',
            'variants.*.price'                   => 'required_with:variants|numeric|min:0',
            'variants.*.compare_price'           => 'nullable|numeric|min:0',
            'variants.*.cost_price'              => 'nullable|numeric|min:0',
            'variants.*.stock_quantity'          => 'nullable|integer|min:0',
            'variants.*.low_stock_threshold'     => 'nullable|integer|min:0',
            'variants.*.weight'                  => 'nullable|numeric|min:0',
            'variants.*.is_active'               => 'boolean',
            'variants.*.sort_order'              => 'nullable|integer|min:0',
            'variants.*.attribute_value_ids'     => 'nullable|array',
            'variants.*.attribute_value_ids.*'   => 'integer|exists:attribute_values,id',
        ];
    }
}
