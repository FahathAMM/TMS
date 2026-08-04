<?php

namespace App\Http\Requests\Tailor;

use Illuminate\Foundation\Http\FormRequest;

class StoreTailorRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'first_name'     => 'required|string|max:50',
            'last_name'      => 'required|string|max:50',
            'phone'          => 'required|string|max:20',
            'specialization' => 'nullable|string|max:50',
            'is_active'      => 'nullable|boolean',
        ];
    }
}
