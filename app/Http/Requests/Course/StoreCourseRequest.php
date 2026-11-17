<?php

namespace App\Http\Requests\Course;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', 'unique:courses,code'],
            'name' => ['required', 'string', 'max:255'],
            'credits' => ['required', 'integer', 'between:1,10'],
            'description' => ['nullable', 'string', 'max:2000'],
            'lecturer_id' => ['nullable', 'integer', 'exists:lecturers,id'],
        ];
    }
}
