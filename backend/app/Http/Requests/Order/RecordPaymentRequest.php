<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class RecordPaymentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'amount'                  => 'required|numeric|min:0.01',
            'payment_method'          => 'required|in:cash,card,bank_transfer,online',
            'payment_type'            => 'required|in:deposit,balance,full',
            'transaction_reference'   => 'nullable|string|max:100',
        ];
    }
}
