<?php

// database/migrations/2026_05_07_190000_add_excluded_from_prize_draw_to_exam_contacts.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Flag for contacts who should never appear in the prize-draw eligibility
 * pool — typically the centre operator (Paul Sheridan) running the draw.
 *
 * Default false. Toggled per-contact via TablePlus or admin tooling. The
 * filter applies to both the student and teacher draws — even if Paul has
 * his own students entered through centre 120, he shouldn't be eligible
 * to win his own quarterly draw.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_contacts', function (Blueprint $table) {
            $table->boolean('excluded_from_prize_draw')
                ->default(false)
                ->after('show_full_name');
        });
    }

    public function down(): void
    {
        Schema::table('exam_contacts', function (Blueprint $table) {
            $table->dropColumn('excluded_from_prize_draw');
        });
    }
};
