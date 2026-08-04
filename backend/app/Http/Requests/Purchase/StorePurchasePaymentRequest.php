<?php

namespace App\Http\Requests\Purchase;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchasePaymentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'amount'         => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,bank_transfer,cheque,mobile_banking,card',
            'payment_date'   => 'required|date',
            'reference'      => 'nullable|string|max:100',
            'notes'          => 'nullable|string',
        ];
    }
}
