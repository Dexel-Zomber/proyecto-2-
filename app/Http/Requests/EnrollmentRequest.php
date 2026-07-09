<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => [
                'required',
                Rule::exists('users', 'id')->where('role', 'student'),
            ],
            'subject_id' => ['required', 'exists:subjects,id'],
        ];
    }
}
