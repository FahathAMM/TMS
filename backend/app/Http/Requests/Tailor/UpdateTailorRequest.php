<?php

namespace App\Http\Requests\Tailor;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTailorRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'first_name'     => 'sometimes|required|string|max:50',
            'last_name'      => 'sometimes|required|string|max:50',
            'phone'          => 'sometimes|required|string|max:20',
            'specialization' => 'nullable|string|max:50',
            'is_active'      => 'nullable|boolean',
        ];
    }
}
