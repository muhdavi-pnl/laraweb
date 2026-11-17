<?php

namespace Database\Factories;

use App\Enums\Semester;
use App\Models\Course;
use App\Models\CourseSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseSchedule>
 */
class CourseScheduleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'academic_year' => '2025/2026',
            'semester' => Semester::Odd,
            'day_of_week' => fake()->numberBetween(1, 5),
            'start_time' => '08:00',
            'end_time' => '09:40',
            'room' => 'R-'.fake()->unique()->numberBetween(100, 999),
        ];
    }
}
