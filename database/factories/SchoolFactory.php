<?php

namespace Database\Factories;

use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<School>
 */
class SchoolFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company() . ' School',
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'postcode' => fake()->postcode(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->safeEmail(),
            'contact_name' => fake()->name(),
            'notes' => null,
        ];
    }
}
