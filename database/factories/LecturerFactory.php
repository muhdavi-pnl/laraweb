<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\Lecturer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lecturer>
 */
class LecturerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => Role::Lecturer]),
            'nidn' => fake()->unique()->numerify('##########'),
            'department' => fake()->randomElement(['Informatics', 'Information Systems', 'Mathematics']),
            'phone' => fake()->numerify('08##########'),
        ];
    }
}
