<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class StoreSeoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'meta_title'       => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords'    => 'nullable|string|max:255',
            'og_title'         => 'nullable|string|max:255',
            'og_description'   => 'nullable|string|max:500',
            'og_image'         => 'nullable|string|max:500',
            'canonical_url'    => 'nullable|url|max:500',
            'schema_markup'    => 'nullable|array',
        ];
    }
}
