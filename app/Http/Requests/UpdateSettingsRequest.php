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
            'alert_warning' => ['required', 'integer', 'between:0,100'],
            'alert_danger' => ['required', 'integer', 'between:0,100'],
        ];
    }
}
