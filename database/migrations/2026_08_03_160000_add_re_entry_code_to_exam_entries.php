<?php

// database/migrations/2026_08_03_160000_add_re_entry_code_to_exam_entries.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When a booked candidate doesn't sit, Trinity issues a Re-entry Permit — a
 * voucher carrying a 100% credit discount, valid twelve months, e.g.
 * "Code: 1-18154879067" for Sam Dobie's Rock and Pop Guitar Grade 4 after the
 * July 2026 session. That code is the only thing that lets the family re-enter
 * for free, and it was living nowhere but a PDF in a downloads folder.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_entries', function (Blueprint $table) {
            $table->string('re_entry_code')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('exam_entries', function (Blueprint $table) {
            $table->dropColumn('re_entry_code');
        });
    }
};
