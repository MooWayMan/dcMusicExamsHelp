<?php

// database/migrations/2026_05_05_120000_create_top_scorer_publications_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Top-scorer publications.
 *
 * When Paul presses "Publish top-scorer awards" on /admin/quarter-end Step 3,
 * a row is written here capturing:
 *
 *   • WHO won (snapshot in `winners` JSON), so a late-arriving higher score
 *     doesn't silently shuffle the leaderboard after publication. If a
 *     bigger score later beats a published winner, Paul tops up the gift
 *     token manually — the published list itself stays stable.
 *
 *   • WHEN it was published, so the public page can say "Awards announced
 *     on 6 May 2026" if we ever want to surface that.
 *
 *   • WHO published it, for audit.
 *
 * One row per quarter (UNIQUE on quarter+year). Re-publishing replaces the
 * snapshot — Paul can do this if e.g. a delayed exam result comes in and
 * he wants to refresh the leaderboard.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('top_scorer_publications', function (Blueprint $table) {
            $table->id();
            $table->integer('quarter');
            $table->integer('year');
            $table->json('winners');                    // snapshot of the four awards
            $table->boolean('finalised_with_pending')->default(false); // true if pending entries existed at publish time
            $table->integer('pending_count')->default(0); // for "X results were pending when published"
            $table->unsignedBigInteger('published_by'); // admin user
            $table->timestamp('published_at');
            $table->timestamps();

            $table->foreign('published_by')->references('id')->on('users');
            $table->unique(['quarter', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('top_scorer_publications');
    }
};
