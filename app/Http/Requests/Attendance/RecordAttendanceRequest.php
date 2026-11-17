<?php

namespace App\Http\Requests\Attendance;

use App\Enums\AttendanceStatus;
use App\Models\Enrollment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RecordAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', $this->route('course'));
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'records' => ['required', 'array', 'min:1', 'max:500'],
            'records.*.enrollment_id' => ['required', 'integer', 'distinct'],
            'records.*.status' => ['required', Rule::enum(AttendanceStatus::class)],
            'records.*.note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $ids = collect($this->validated('records'))->pluck('enrollment_id')->unique();
            $valid = Enrollment::where('course_id', $this->route('course')->id)->whereIn('id', $ids)->count();

            if ($valid !== $ids->count()) {
                $validator->errors()->add('records', 'Every enrollment_id must belong to this course.');
            }
        }];
    }
}
