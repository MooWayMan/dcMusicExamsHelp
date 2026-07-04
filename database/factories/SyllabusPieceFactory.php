<?php

namespace Database\Factories;

use App\Models\SyllabusPiece;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SyllabusPiece>
 */
class SyllabusPieceFactory extends Factory
{
    protected $model = SyllabusPiece::class;

    public function definition(): array
    {
        return [
            'exam_board' => 'Trinity',
            'exam_stream' => 'Classical & Jazz',
            'instrument' => 'Piano',
            'variant' => null,
            'grade' => fake()->randomElement(['Grade 1', 'Grade 2', 'Grade 3']),
            'position' => fake()->numberBetween(1, 20),
            'composer' => fake()->name(),
            'title' => fake()->unique()->sentence(3),
            'book_title' => null,
            'buy_kind' => 'none',
        ];
    }

    public function rockAndPop(): static
    {
        return $this->state(fn () => [
            'exam_stream' => 'Rock & Pop',
            'instrument' => 'Guitar (Rock/Pop)',
        ]);
    }
}
