<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class QcRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'passed' => 'required|boolean',
            'notes'  => 'nullable|string',
        ];
    }
}
