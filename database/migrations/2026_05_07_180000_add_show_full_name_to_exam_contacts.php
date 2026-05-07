<?php

// database/migrations/2026_05_07_180000_add_show_full_name_to_exam_contacts.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirror of the same flag on `exam_entries` (where it gates whether a
 * candidate's full name appears on the public Recognition page).
 *
 * On `exam_contacts` it gates whether a teacher's full name appears on the
 * authenticated /dashboard prize-draw widget. Default false — every teacher
 * starts as "First L" until they explicitly opt in (typically via reply to
 * the email Paul sends with their gift token).
 *
 * School admins are unaffected: when the dashboard renders a school admin's
 * draw win it shows the linked SCHOOL name, not the personal name, so this
 * flag never gets consulted for them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_contacts', function (Blueprint $table) {
            $table->boolean('show_full_name')->default(false)->after('contacted_by_email');
        });
    }

    public function down(): void
    {
        Schema::table('exam_contacts', function (Blueprint $table) {
            $table->dropColumn('show_full_name');
        });
    }
};
