<?php

namespace App\Http\Requests\GarmentPrice;

use Illuminate\Foundation\Http\FormRequest;

class StoreGarmentPriceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'garment_type'  => 'required|string|max:100',
            'fabric_source' => 'nullable|in:customer_provided,in_house',
            'price'         => 'required|numeric|min:0',
            'is_active'     => 'nullable|boolean',
        ];
    }
}
