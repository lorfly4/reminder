<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'timezone' => ['sometimes', 'timezone:all'],
            'language' => ['sometimes', 'in:id,en'],
            'theme' => ['sometimes', 'in:light,dark,system'],
        ];
    }
}
