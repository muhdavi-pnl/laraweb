<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => Role::Student]),
            'advisor_id' => null,
            'nim' => fake()->unique()->numerify('##########'),
            'study_program' => fake()->randomElement(['Informatics', 'Information Systems', 'Mathematics']),
            'entry_year' => fake()->numberBetween(2018, (int) date('Y')),
            'phone' => fake()->numerify('08##########'),
            'address' => fake()->streetAddress(),
        ];
    }
}
