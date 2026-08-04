<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class AssignTailorRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'tailor_id'      => 'required|exists:tailors,id',
            'assigned_role'  => 'nullable|string|max:50',
        ];
    }
}
