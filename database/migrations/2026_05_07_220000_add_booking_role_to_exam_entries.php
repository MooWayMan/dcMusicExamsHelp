<?php

// database/migrations/2026_05_07_220000_add_booking_role_to_exam_entries.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-entry override for "in this exam, with this candidate, was the
 * teacher_name acting as TEACHER, PARENT, or SELF?". Solves the multi-type
 * contact problem (e.g. Alexandra Bibby = teacher + parent on her contact
 * record from years ago, but in Q1 2026 she's entered Sam Williamson as
 * his teacher, not as a parent — the parent type is legacy).
 *
 * When set, it wins over the contact-type-based inference. When NULL
 * (the typical case for clean bookings), the controller falls back to
 * the existing contact-type lookup.
 *
 * Values: 'teacher' | 'parent' | 'self' | null. Stored as plain string
 * rather than a Postgres enum so adding values later doesn't need a
 * migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_entries', function (Blueprint $table) {
            $table->string('booking_role', 32)->nullable()->after('teacher_name');
        });
    }

    public function down(): void
    {
        Schema::table('exam_entries', function (Blueprint $table) {
            $table->dropColumn('booking_role');
        });
    }
};
