<?php

// database/migrations/2026_04_25_115000_drop_role_from_exam_contacts.php
//
// Phase D-3, Step 6 — drop the legacy `exam_contacts.role` column. The
// unified contacts model has multi-type membership via the contact_types
// pivot (teacher, parent, candidate, school_admin, trinity_admin,
// subscriber). The single-string `role` column is no longer read by
// the app and was creating ambiguity for multi-type contacts.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_contacts', function (Blueprint $table) {
            // The original migration indexed `role`. Drop the index first
            // so the dropColumn doesn't fail on Postgres in some configs.
            try {
                $table->dropIndex(['role']);
            } catch (\Throwable $e) {
                // Index may not exist on environments where it was named
                // differently; the column drop below is the load-bearing op.
            }
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        Schema::table('exam_contacts', function (Blueprint $table) {
            $table->string('role')->nullable()->index();
        });
    }
};
