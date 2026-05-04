<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a `marketing_consent_at` timestamp to the subscribers table so we
 * can distinguish between people who downloaded the lead magnet (no
 * marketing consent) and those who actively opted in to receive ongoing
 * updates (consent timestamp recorded).
 *
 * GDPR: a null value here means we MUST NOT send marketing comms to that
 * subscriber — only the transactional lead magnet delivery itself.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscribers', function (Blueprint $table) {
            // No `after()` — Postgres ignores column ordering in
            // ALTER TABLE so adding it would be misleading.
            $table->timestamp('marketing_consent_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('subscribers', function (Blueprint $table) {
            $table->dropColumn('marketing_consent_at');
        });
    }
};
