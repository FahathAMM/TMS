<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class AdvanceStatusRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'production_status' => 'required|in:pending,cutting,stitching,qc,rework,ready,delivered',
        ];
    }
}
