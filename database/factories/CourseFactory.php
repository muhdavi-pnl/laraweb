<?php

namespace Database\Factories;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('??###')),
            'name' => fake()->unique()->words(3, true),
            'credits' => fake()->numberBetween(1, 4),
            'description' => fake()->sentence(),
            'lecturer_id' => null,
        ];
    }
}
