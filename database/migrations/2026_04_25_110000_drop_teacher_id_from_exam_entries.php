<?php

// database/migrations/2026_04_25_110000_drop_teacher_id_from_exam_entries.php
//
// Phase D-3, Step 1 — drop legacy FK from exam_entries to the standalone
// `teachers` table. The unified contacts model has fully replaced it via
// `teacher_contact_id` (→ exam_contacts). Nothing in the app reads
// `exam_entries.teacher_id` anymore.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_entries', function (Blueprint $table) {
            // dropConstrainedForeignId handles both the FK constraint and the column.
            $table->dropConstrainedForeignId('teacher_id');
        });
    }

    public function down(): void
    {
        Schema::table('exam_entries', function (Blueprint $table) {
            $table->foreignId('teacher_id')
                ->nullable()
                ->after('teacher_name')
                ->constrained()
                ->nullOnDelete();
        });
    }
};
