<?php

// database/migrations/2026_06_21_130000_add_alt_buy_to_syllabus_pieces.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Secondary buy option — e.g. piano "Plus Exercises" pieces lead with the
        // Extended edition but are also available in the cheaper Standard book.
        Schema::table('syllabus_pieces', function (Blueprint $table) {
            $table->string('buy_alt_url')->nullable()->after('buy_edition');
            $table->string('buy_alt_edition')->nullable()->after('buy_alt_url');
        });
    }

    public function down(): void
    {
        Schema::table('syllabus_pieces', function (Blueprint $table) {
            $table->dropColumn(['buy_alt_url', 'buy_alt_edition']);
        });
    }
};
