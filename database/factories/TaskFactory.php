<?php

// database/factories/TaskFactory.php

namespace Database\Factories;

use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'detail' => fake()->optional()->sentence(),
            'notes' => null,
            'priority' => fake()->randomElement(Task::PRIORITIES),
            'status' => 'pending',
            'assigned_to' => fake()->randomElement(['Paul', 'Spider-Man', 'Paul & Spider-Man']),
            'category' => fake()->randomElement(Task::CATEGORIES),
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function withNotes(string $notes = 'Some test notes'): static
    {
        return $this->state(fn () => ['notes' => $notes]);
    }

    public function high(): static
    {
        return $this->state(fn () => ['priority' => 'high']);
    }

    public function medium(): static
    {
        return $this->state(fn () => ['priority' => 'medium']);
    }

    public function low(): static
    {
        return $this->state(fn () => ['priority' => 'low']);
    }
}
