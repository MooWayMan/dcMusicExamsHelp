<?php

// database/migrations/2026_04_25_080400_add_exam_contact_id_to_contact_logs.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Adds exam_contact_id to contact_logs so logs can point at the
     * unified contact. user_id stays for now (backfill copies forward,
     * dropped in a later migration once all reads are off it).
     */
    public function up(): void
    {
        Schema::table('contact_logs', function (Blueprint $table) {
            $table->foreignId('exam_contact_id')
                ->nullable()
                ->after('user_id')
                ->constrained()
                ->nullOnDelete();

            $table->index('exam_contact_id');
        });
    }

    public function down(): void
    {
        Schema::table('contact_logs', function (Blueprint $table) {
            $table->dropForeign(['exam_contact_id']);
            $table->dropColumn('exam_contact_id');
        });
    }
};
