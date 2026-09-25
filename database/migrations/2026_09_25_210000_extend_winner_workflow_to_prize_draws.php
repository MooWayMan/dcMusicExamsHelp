<?php

// database/migrations/2026_09_25_210000_extend_winner_workflow_to_prize_draws.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The top-scorer tick boxes become the tick boxes for every Quarter End prize:
 * the four top-scorer awards, the student draw and the teacher draw.
 *
 * Renamed to prize_workflow because it no longer tracks top scorers only.
 * Existing rows keep their meaning: `bought` is still the card bought and
 * `sent` is still the winner email sent. Three steps are added for the
 * claim-first route (the parent emails to claim, the card is bought then):
 * `claimed`, `card_sent` and `used`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('top_scorer_workflow', 'prize_workflow');

        Schema::table('prize_workflow', function (Blueprint $table) {
            $table->boolean('claimed')->default(false);
            $table->boolean('card_sent')->default(false);
            $table->boolean('used')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('prize_workflow', function (Blueprint $table) {
            $table->dropColumn(['claimed', 'card_sent', 'used']);
        });

        Schema::rename('prize_workflow', 'top_scorer_workflow');
    }
};
