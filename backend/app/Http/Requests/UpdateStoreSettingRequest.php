<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStoreSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if ($this->route('group') === 'currency') {
            return [
                'currency_code'       => 'required|string|max:10',
                'currency_symbol'     => 'required|string|max:10',
                'currency_position'   => 'nullable|in:left,right',
                'decimal_places'      => 'nullable|integer|min:0|max:4',
                'decimal_separator'   => 'nullable|string|max:1',
                'thousand_separator'  => 'nullable|string|max:1',
            ];
        }

        return [
            '*' => 'nullable',
        ];
    }
}
