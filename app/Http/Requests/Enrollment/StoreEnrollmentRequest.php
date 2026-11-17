<?php

namespace App\Http\Requests\Enrollment;

use App\Enums\Role;
use App\Enums\Semester;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Students always enroll themselves; any supplied student_id is overridden. */
    protected function prepareForValidation(): void
    {
        $user = $this->user();

        if ($user?->hasRole(Role::Student)) {
            $this->merge(['student_id' => $user->student?->id]);
        }
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'course_id' => [
                'required', 'integer', 'exists:courses,id',
                Rule::unique('enrollments')->where(fn ($q) => $q
                    ->where('student_id', $this->input('student_id'))
                    ->where('academic_year', $this->input('academic_year'))
                    ->where('semester', $this->input('semester'))),
            ],
            'academic_year' => ['required', 'string', 'regex:/^\d{4}\/\d{4}$/'],
            'semester' => ['required', Rule::enum(Semester::class)],
        ];
    }

    public function messages(): array
    {
        return ['course_id.unique' => 'The student is already enrolled in this course for that term.'];
    }
}
