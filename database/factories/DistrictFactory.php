<?php

namespace Database\Factories;

use App\Models\District;
use App\Models\Regency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<District>
 */
class DistrictFactory extends Factory
{
    public function definition(): array
    {
        $regency = Regency::factory()->create();

        return [
            'code' => $regency->code.'.'.str_pad((string) fake()->unique()->numberBetween(1, 99), 2, '0', STR_PAD_LEFT),
            'regency_code' => $regency->code,
            'name' => fake()->unique()->streetName(),
        ];
    }
}
