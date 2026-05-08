<?php

// database/migrations/2026_05_08_120000_create_import_runs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * import_runs — audit log of every CSV import Paul commits via the
 * /admin/imports page. One row per Commit press (preview-only does not
 * write to this table). Used by the history table at the bottom of the
 * page so Paul can see what he imported and when, plus a JSON summary
 * with counts (created/updated/skipped).
 *
 * type:
 *   • 'bulk_orders'      — Section 1 quarterly orders CSV
 *   • 'candidate_triple' — Section 2 enrolment + summary + marksheet
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('type', 32);
            $table->string('filename')->nullable();
            $table->json('summary')->nullable();
            $table->timestamps();

            $table->index(['type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_runs');
    }
};
