<?php

namespace App\Http\Requests\Expense;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'category'       => 'required|string|max:100',
            'description'    => 'nullable|string|max:255',
            'amount'         => 'required|numeric|min:0.01',
            'expense_date'   => 'required|date',
            'payment_method' => 'nullable|in:cash,bank',
        ];
    }
}
