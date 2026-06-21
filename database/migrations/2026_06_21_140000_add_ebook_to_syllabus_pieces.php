<?php

// database/migrations/2026_06_21_140000_add_ebook_to_syllabus_pieces.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Digital edition link (Trinity ebook store, with Paul's affiliate) for
        // Trinity-published books — sits alongside the Amazon print link.
        Schema::table('syllabus_pieces', function (Blueprint $table) {
            $table->string('buy_ebook_url')->nullable()->after('buy_alt_edition');
        });
    }

    public function down(): void
    {
        Schema::table('syllabus_pieces', function (Blueprint $table) {
            $table->dropColumn('buy_ebook_url');
        });
    }
};
