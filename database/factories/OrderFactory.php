<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'school_id' => School::factory(),
            'trinity_order_number' => 'TRN-' . fake()->unique()->numerify('######'),
            'delivery_method' => fake()->randomElement(['digital', 'default']),
            'subject_area' => fake()->randomElement(['Music', 'Drama', 'Rock & Pop']),
            'candidates' => fake()->numberBetween(1, 20),
            'venue' => fake()->company(),
            'order_status' => fake()->randomElement(['pending', 'confirmed', 'completed']),
            'requested_start_date' => fake()->dateTimeBetween('+1 week', '+3 months'),
            'commission_rate' => fake()->randomFloat(2, 5, 15),
            'commission_amount' => fake()->randomFloat(2, 20, 500),
            'notes' => null,
        ];
    }

    public function digital(): static
    {
        return $this->state(fn () => ['delivery_method' => 'digital']);
    }

    public function faceToFace(): static
    {
        return $this->state(fn () => ['delivery_method' => 'default']);
    }
}
