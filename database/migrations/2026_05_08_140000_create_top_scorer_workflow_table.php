<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-winner workflow tracking for quarter-end top-scorer awards.
     *
     * Three manual progress steps per winner row on /admin/quarter-end:
     *   - bought: gift card purchased on Amazon
     *   - sent:   personalised winner email sent
     *   - cert:   certificate attached to the email
     *
     * Keyed by quarter + year + award_key + winner_full_name so tied winners
     * each get their own row. winner_full_name (not entry_id) so a re-run of
     * the awards calculation doesn't orphan progress against stale entry IDs.
     */
    public function up(): void
    {
        Schema::create('top_scorer_workflow', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('quarter');
            $table->unsignedSmallInteger('year');
            $table->string('award_key');
            $table->string('winner_full_name');
            $table->boolean('bought')->default(false);
            $table->boolean('sent')->default(false);
            $table->boolean('cert')->default(false);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['quarter', 'year', 'award_key', 'winner_full_name'],
                'top_scorer_workflow_winner_unique'
            );
            $table->index(['quarter', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('top_scorer_workflow');
    }
};
