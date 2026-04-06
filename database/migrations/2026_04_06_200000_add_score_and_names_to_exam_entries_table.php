<?php

// database/migrations/2026_04_06_200000_add_score_and_names_to_exam_entries_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_entries', function (Blueprint $table) {
            $table->unsignedSmallInteger('score')->nullable()->after('result');
            $table->string('candidate_name')->nullable()->after('instrument_id');
            $table->string('teacher_name')->nullable()->after('candidate_name');
            $table->string('school_name')->nullable()->after('teacher_name');
        });
    }

    public function down(): void
    {
        Schema::table('exam_entries', function (Blueprint $table) {
            $table->dropColumn(['score', 'candidate_name', 'teacher_name', 'school_name']);
        });
    }
};
