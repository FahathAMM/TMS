<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class StoreMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file'       => 'required|file|mimes:jpg,jpeg,png,webp,gif,mp4,mov,avi,webm|max:102400',
            'alt'        => 'nullable|string|max:255',
            'title'      => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'is_primary' => 'boolean',
            'variant_id' => 'nullable|integer|exists:product_variants,id',
        ];
    }
}
