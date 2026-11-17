<?php

namespace Database\Factories;

use App\Enums\Semester;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enrollment>
 */
class EnrollmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'course_id' => Course::factory(),
            'academic_year' => '2025/2026',
            'semester' => Semester::Odd,
        ];
    }
}
