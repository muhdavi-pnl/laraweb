<?php

namespace Database\Factories;

use App\Models\Province;
use App\Models\Regency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Regency>
 */
class RegencyFactory extends Factory
{
    public function definition(): array
    {
        $province = Province::factory()->create();

        return [
            'code' => $province->code.'.'.str_pad((string) fake()->unique()->numberBetween(1, 99), 2, '0', STR_PAD_LEFT),
            'province_code' => $province->code,
            'name' => 'Kabupaten '.fake()->unique()->city(),
        ];
    }
}
