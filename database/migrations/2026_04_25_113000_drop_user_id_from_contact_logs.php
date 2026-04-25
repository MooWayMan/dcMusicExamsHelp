<?php

// database/migrations/2026_04_25_113000_drop_user_id_from_contact_logs.php
//
// Phase D-3, Step 4 — drop the legacy `user_id` FK on contact_logs.
// Replaced by `exam_contact_id` (→ exam_contacts) when the unified
// contacts model shipped. The column is empty on prod.
//
// Defensive: discover the FK constraint name at runtime and drop it,
// then drop the column. Handles environments where the FK has a
// different name or doesn't exist at all.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('contact_logs', 'user_id')) {
            return;
        }

        $constraints = DB::select(<<<SQL
            SELECT tc.constraint_name
            FROM information_schema.table_constraints tc
            JOIN information_schema.key_column_usage kcu
              ON tc.constraint_name = kcu.constraint_name
             AND tc.table_schema = kcu.table_schema
            WHERE tc.table_name = 'contact_logs'
              AND tc.constraint_type = 'FOREIGN KEY'
              AND kcu.column_name = 'user_id'
        SQL);

        foreach ($constraints as $row) {
            DB::statement('ALTER TABLE contact_logs DROP CONSTRAINT "'.$row->constraint_name.'"');
        }

        Schema::table('contact_logs', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('contact_logs', 'user_id')) {
            return;
        }

        Schema::table('contact_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
        });
    }
};
