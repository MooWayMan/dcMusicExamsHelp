<?php

namespace Database\Factories;

use App\Models\Subscriber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscriber>
 */
class SubscriberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'role' => fake()->randomElement(['teacher', 'parent', 'student', null]),
            'source' => fake()->randomElement(['website', 'landing', 'email', 'hero', 'bottom']),
            'subscribed_at' => now(),
            'unsubscribed_at' => null,
        ];
    }

    /**
     * Indicate that the subscriber has unsubscribed.
     */
    public function unsubscribed(): static
    {
        return $this->state(fn (array $attributes) => [
            'unsubscribed_at' => now(),
        ]);
    }

    /**
     * Indicate that the subscriber is a teacher.
     */
    public function asTeacher(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'teacher',
        ]);
    }

    /**
     * Indicate that the subscriber is a parent.
     */
    public function asParent(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'parent',
        ]);
    }

    /**
     * Indicate that the subscriber is a student.
     */
    public function asStudent(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'student',
        ]);
    }
}
