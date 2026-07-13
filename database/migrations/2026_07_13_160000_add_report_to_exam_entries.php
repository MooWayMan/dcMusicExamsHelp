<?php

// database/migrations/2026_07_13_160000_add_report_to_exam_entries.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stores the full F2F examination report captured from the handwritten scan:
 * each piece/section's name, mark, max and the examiner's transcribed comment,
 * plus the examiner number and general comments. The digital results don't
 * carry the piece names or written feedback, so this is the record teachers
 * can eventually be shown. Display-only — nothing here is queried, so a single
 * JSON column is the right home.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_entries', function (Blueprint $table) {
            $table->json('report')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('exam_entries', function (Blueprint $table) {
            $table->dropColumn('report');
        });
    }
};
