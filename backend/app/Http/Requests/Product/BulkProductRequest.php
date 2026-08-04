<?php

namespace App\Http\Requests\Product;

use App\Enums\ProductStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $statusValues = array_column(ProductStatus::cases(), 'value');

        return [
            'ids'    => 'required|array|min:1',
            'ids.*'  => 'integer|exists:products,id',
            'action' => ['required', 'string', Rule::in([...$statusValues, 'delete'])],
        ];
    }
}
