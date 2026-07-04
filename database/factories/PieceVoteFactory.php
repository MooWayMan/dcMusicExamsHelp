<?php

namespace Database\Factories;

use App\Models\PieceVote;
use App\Models\SyllabusPiece;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PieceVote>
 */
class PieceVoteFactory extends Factory
{
    protected $model = PieceVote::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => 'teacher']),
            'syllabus_piece_id' => SyllabusPiece::factory(),
            'rating' => fake()->numberBetween(1, 4),
            'used_count' => fake()->numberBetween(0, 12),
        ];
    }
}
