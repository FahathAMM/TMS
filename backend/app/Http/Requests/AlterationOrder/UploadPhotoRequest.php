<?php

namespace App\Http\Requests\AlterationOrder;

use Illuminate\Foundation\Http\FormRequest;

class UploadPhotoRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:jpeg,jpg,png,webp|max:5120',
            'type' => 'required|in:before,after',
        ];
    }
}
