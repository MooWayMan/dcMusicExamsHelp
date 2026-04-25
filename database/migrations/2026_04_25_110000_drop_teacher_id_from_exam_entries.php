<?php

// database/migrations/2026_04_25_110000_drop_teacher_id_from_exam_entries.php
//
// Phase D-3, Step 1 — drop legacy FK from exam_entries to the standalone
// `teachers` table. The unified contacts model has fully replaced it via
// `teacher_contact_id` (→ exam_contacts). Nothing in the app reads
// `exam_entries.teacher_id` anymore.
//
// Defensive: prod's constraint name may differ from local's, and on some
// environments the FK was never created. We discover the actual constraint
// name from information_schema and drop it (if any), then drop the column.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('exam_entries', 'teacher_id')) {
            return;
        }

        // Drop any FK constraint on exam_entries.teacher_id, by whatever name
        // PostgreSQL gave it. Iterating handles cases where the constraint
        // name differs across environments (or doesn't exist at all).
        $constraints = DB::select(<<<SQL
            SELECT tc.constraint_name
            FROM information_schema.table_constraints tc
            JOIN information_schema.key_column_usage kcu
              ON tc.constraint_name = kcu.constraint_name
             AND tc.table_schema = kcu.table_schema
            WHERE tc.table_name = 'exam_entries'
              AND tc.constraint_type = 'FOREIGN KEY'
              AND kcu.column_name = 'teacher_id'
        SQL);

        foreach ($constraints as $row) {
            DB::statement('ALTER TABLE exam_entries DROP CONSTRAINT "'.$row->constraint_name.'"');
        }

        Schema::table('exam_entries', function (Blueprint $table) {
            $table->dropColumn('teacher_id');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('exam_entries', 'teacher_id')) {
            return;
        }

        Schema::table('exam_entries', function (Blueprint $table) {
            $table->unsignedBigInteger('teacher_id')->nullable()->after('teacher_name');
            // Skip re-adding the FK constraint since the teachers table is gone.
        });
    }
};
