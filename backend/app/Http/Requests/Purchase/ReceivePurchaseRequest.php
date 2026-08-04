<?php

namespace App\Http\Requests\Purchase;

use Illuminate\Foundation\Http\FormRequest;

class ReceivePurchaseRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'items'                           => 'required|array|min:1',
            'items.*.purchase_item_id'        => 'required|exists:purchase_items,id',
            'items.*.quantity_received'       => 'required|numeric|min:0.001',
        ];
    }
}
