<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class AdjustStockRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
            'type' => 'required|in:addition,subtraction,damage,correction',
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:500',
        ];
    }
}
