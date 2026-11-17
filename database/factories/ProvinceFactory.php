<?php

namespace Database\Factories;

use App\Models\Province;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Province>
 */
class ProvinceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => (string) fake()->unique()->numberBetween(11, 99),
            'name' => fake()->unique()->state(),
        ];
    }
}
