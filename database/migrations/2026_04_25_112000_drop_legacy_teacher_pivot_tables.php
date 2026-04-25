<?php

// database/migrations/2026_04_25_112000_drop_legacy_teacher_pivot_tables.php
//
// Phase D-3, Step 3 — drop the three legacy User-keyed pivots that are
// fully replaced by the contact_school + contact_instrument tables on
// the unified contacts model. teacher_subject_area has no replacement
// (subject_areas table itself is also gone).

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // teacher_subject_area depends on subject_areas, so drop it first.
        Schema::dropIfExists('teacher_subject_area');
        Schema::dropIfExists('teacher_instrument');
        Schema::dropIfExists('teacher_school');

        // subject_areas was only ever used via teacher_subject_area; with
        // that pivot gone the lookup table has no remaining consumers.
        Schema::dropIfExists('subject_areas');
    }

    public function down(): void
    {
        // Irreversible — see 2026_03_31_1003/4/5/6 for the original schemas.
    }
};
