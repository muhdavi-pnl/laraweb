<?php

namespace App\Http\Requests\Student;

use App\Http\Requests\Concerns\ValidatesAddress;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreStudentRequest extends FormRequest
{
    use ValidatesAddress;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(8)],
            'nim' => ['required', 'string', 'max:20', 'unique:students,nim'],
            'study_program' => ['required', 'string', 'max:255'],
            'entry_year' => ['required', 'integer', 'between:1990,'.date('Y')],
            'phone' => ['nullable', 'string', 'max:20'],
            'advisor_id' => ['nullable', 'integer', 'exists:lecturers,id'],
        ] + $this->addressRules();
    }
}
