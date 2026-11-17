<?php

namespace App\Http\Requests\Course;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['sometimes', 'string', 'max:20', Rule::unique('courses', 'code')->ignore($this->route('course'))],
            'name' => ['sometimes', 'string', 'max:255'],
            'credits' => ['sometimes', 'integer', 'between:1,10'],
            'description' => ['nullable', 'string', 'max:2000'],
            'lecturer_id' => ['nullable', 'integer', 'exists:lecturers,id'],
        ];
    }
}
