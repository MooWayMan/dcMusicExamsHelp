<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracks when the weekly "results in — here's your cert" email has been
 * sent for an exam entry. Drives the Weekly Send section on
 * /admin/certificates so Paul doesn't re-email the same teacher about the
 * same student week after week.
 *
 * NULL  → not yet emailed this cycle (appears in the Weekly Send list).
 * Date  → marked sent on this timestamp (hidden from Weekly Send).
 *
 * Separate from the end-of-quarter batch — the QuarterEnd Step 2 "Mark as
 * Sent" toggle lives in localStorage, intentionally, because the
 * quarter-end ceremony is a single point-in-time push. The weekly drip
 * needs a persisted truth so the same student doesn't reappear next
 * Sunday after Paul refreshes the page.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_entries', function (Blueprint $table) {
            $table->timestamp('certificate_sent_at')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('exam_entries', function (Blueprint $table) {
            $table->dropColumn('certificate_sent_at');
        });
    }
};
