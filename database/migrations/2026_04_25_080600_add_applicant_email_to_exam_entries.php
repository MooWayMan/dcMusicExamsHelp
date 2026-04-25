<?php

// database/migrations/2026_04_25_080600_add_applicant_email_to_exam_entries.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Per-candidate applicant email from Trinity CSV. Up to now we've
     * only stored applicant_email on `orders` (one per order), but on
     * F2F orders each candidate can have a distinct applicant.
     */
    public function up(): void
    {
        Schema::table('exam_entries', function (Blueprint $table) {
            $table->string('applicant_email')->nullable()->after('candidate_name');
        });
    }

    public function down(): void
    {
        Schema::table('exam_entries', function (Blueprint $table) {
            $table->dropColumn('applicant_email');
        });
    }
};
