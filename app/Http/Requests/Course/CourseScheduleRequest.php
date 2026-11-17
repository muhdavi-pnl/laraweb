<?php

namespace App\Http\Requests\Course;

use App\Enums\Semester;
use App\Models\CourseSchedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** Handles both create (POST) and partial update (PATCH/PUT) of a schedule slot. */
class CourseScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $required = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'academic_year' => [$required, 'string', 'regex:/^\d{4}\/\d{4}$/'],
            'semester' => [$required, Rule::enum(Semester::class)],
            'day_of_week' => [$required, 'integer', 'between:1,7'],
            'start_time' => [$required, 'date_format:H:i'],
            'end_time' => [$required, 'date_format:H:i', 'after:start_time'],
            'room' => [$required, 'string', 'max:50'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $current = $this->route('schedule');
            $slot = array_merge($current?->only(['academic_year', 'semester', 'day_of_week', 'start_time', 'end_time', 'room']) ?? [], $this->validated());
            $slot['semester'] = $slot['semester'] instanceof Semester ? $slot['semester']->value : $slot['semester'];

            $clash = CourseSchedule::query()
                ->when($current, fn ($q) => $q->whereKeyNot($current->id))
                ->where('academic_year', $slot['academic_year'])
                ->where('semester', $slot['semester'])
                ->where('day_of_week', $slot['day_of_week'])
                ->where('room', $slot['room'])
                ->where('start_time', '<', $slot['end_time'])
                ->where('end_time', '>', $slot['start_time'])
                ->exists();

            if ($clash) {
                $validator->errors()->add('room', 'The room is already booked for an overlapping time slot.');
            }
        }];
    }
}
