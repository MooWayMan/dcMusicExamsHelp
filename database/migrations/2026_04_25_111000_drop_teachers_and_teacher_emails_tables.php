<?php

// database/migrations/2026_04_25_111000_drop_teachers_and_teacher_emails_tables.php
//
// Phase D-3, Step 2 — drop the standalone `teachers` and `teacher_emails`
// tables. Replaced by the unified `exam_contacts` + `contact_emails`
// model. Order matters: teacher_emails first (FK → teachers),
// then school_teacher pivot (FK → teachers), then teachers itself.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pivot table created alongside `teachers` in 2026_04_13 — also keyed
        // on teacher_id, so it must go before the parent table is dropped.
        Schema::dropIfExists('school_teacher');
        Schema::dropIfExists('teacher_emails');
        Schema::dropIfExists('teachers');
    }

    public function down(): void
    {
        // Irreversible — see 2026_04_13_150000 for the original schema if a
        // rebuild is ever needed.
    }
};
