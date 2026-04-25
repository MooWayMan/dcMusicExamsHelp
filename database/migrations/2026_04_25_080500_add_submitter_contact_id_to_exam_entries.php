<?php

// database/migrations/2026_04_25_080500_add_submitter_contact_id_to_exam_entries.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Distinguishes who PLACED the order with Trinity (submitter)
     * from who actually TEACHES the candidate (teacher_contact_id).
     *
     * Solves the Adrian O'Malley case: he submitted Jasper's exam, but
     * he's the parent, not the teacher. teacher_contact_id should be the
     * actual teacher; submitter_contact_id is Adrian.
     */
    public function up(): void
    {
        Schema::table('exam_entries', function (Blueprint $table) {
            $table->foreignId('submitter_contact_id')
                ->nullable()
                ->after('teacher_contact_id')
                ->constrained('exam_contacts')
                ->nullOnDelete();

            $table->index('submitter_contact_id');
        });
    }

    public function down(): void
    {
        Schema::table('exam_entries', function (Blueprint $table) {
            $table->dropForeign(['submitter_contact_id']);
            $table->dropColumn('submitter_contact_id');
        });
    }
};
