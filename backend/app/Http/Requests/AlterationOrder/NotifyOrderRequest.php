<?php

namespace App\Http\Requests\AlterationOrder;

use Illuminate\Foundation\Http\FormRequest;

class NotifyOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'message' => 'nullable|string|max:500',
        ];
    }
}
