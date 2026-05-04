<?php

// database/migrations/2026_05_02_105408_add_date_of_birth_to_exam_entries_table.php
//
// Trinity's bulk Results Enquiry CSV doesn't expose date of birth, so teachers
// have to look it up per-candidate in the portal. We're adding the column here
// (denormalised: same DOB will repeat across multiple exam_entries for the
// same candidate_number) so Paul can populate it once and the user dashboard
// can show it as a reference list. When we build the canonical "My Students"
// table next session, DOB moves there and this column becomes the source for
// the migration.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_entries', function (Blueprint $table) {
            $table->date('date_of_birth')->nullable()->after('candidate_name');
        });
    }

    public function down(): void
    {
        Schema::table('exam_entries', function (Blueprint $table) {
            $table->dropColumn('date_of_birth');
        });
    }
};
