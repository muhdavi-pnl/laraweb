<?php

namespace App\Http\Requests\Student;

use App\Http\Requests\Concerns\ValidatesAddress;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateStudentRequest extends FormRequest
{
    use ValidatesAddress;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $student = $this->route('student');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($student?->user_id)],
            'password' => ['sometimes', 'string', Password::min(8)],
            'nim' => ['sometimes', 'string', 'max:20', Rule::unique('students', 'nim')->ignore($student?->id)],
            'study_program' => ['sometimes', 'string', 'max:255'],
            'entry_year' => ['sometimes', 'integer', 'between:1990,'.date('Y')],
            'phone' => ['nullable', 'string', 'max:20'],
            'advisor_id' => ['nullable', 'integer', 'exists:lecturers,id'],
        ] + $this->addressRules();
    }
}
