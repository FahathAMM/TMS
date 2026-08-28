<?php

namespace App\Http\Requests\AlterationOrder;

use Illuminate\Foundation\Http\FormRequest;

class AssignTailorRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'tailor_id' => 'required|exists:tailors,id',
        ];
    }
}
