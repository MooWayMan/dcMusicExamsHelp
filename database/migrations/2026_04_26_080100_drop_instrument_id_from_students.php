<?php

// database/migrations/2026_04_26_080100_drop_instrument_id_from_students.php
//
// Drop `students.instrument_id`. A student naturally takes more than one
// instrument over time (Piano + Drums grade entries from the same kid),
// so a single FK on students is misleading and was always going to be
// wrong sometimes. Instrument lives on `exam_entries.instrument_id` (per-
// exam), which is already the source of truth.
//
// Defensive FK drop (constraint name discovery), then column drop.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('students', 'instrument_id')) {
            return;
        }

        $constraints = DB::select(<<<SQL
            SELECT tc.constraint_name
            FROM information_schema.table_constraints tc
            JOIN information_schema.key_column_usage kcu
              ON tc.constraint_name = kcu.constraint_name
             AND tc.table_schema = kcu.table_schema
            WHERE tc.table_name = 'students'
              AND tc.constraint_type = 'FOREIGN KEY'
              AND kcu.column_name = 'instrument_id'
        SQL);

        foreach ($constraints as $row) {
            DB::statement('ALTER TABLE students DROP CONSTRAINT "'.$row->constraint_name.'"');
        }

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('instrument_id');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('students', 'instrument_id')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            $table->unsignedBigInteger('instrument_id')->nullable()->after('email');
        });
    }
};
