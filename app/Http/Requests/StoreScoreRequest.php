<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreScoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject_id' => ['required', 'exists:subjects,id'],
            'student_id' => [
                'required',
                Rule::exists('users', 'id')->where('role', 'student'),
            ],
            'label' => ['nullable', 'string', 'max:50'],
            'value' => ['required', 'numeric', 'between:0,100'],
        ];
    }
}
