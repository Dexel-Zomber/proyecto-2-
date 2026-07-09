<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $courseId = $this->route('course')?->id;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('courses', 'name')->ignore($courseId)],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
