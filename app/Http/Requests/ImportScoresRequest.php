<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportScoresRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'scores_file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ];
    }
}
