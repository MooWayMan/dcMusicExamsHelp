<?php

// database/migrations/2026_07_04_121000_swap_piece_votes_used_count_for_band.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replace the free-number used_count with a capped 1-3 band (a few /
     * regularly / loads). This makes the public Top Ten chart hard to game:
     * a single account can no longer inflate a piece with a huge number — it
     * contributes at most one band. The create-table migration already shipped
     * to dev/staging, so we ALTER rather than edit it.
     */
    public function up(): void
    {
        Schema::table('piece_votes', function (Blueprint $table) {
            $table->dropColumn('used_count');
        });

        Schema::table('piece_votes', function (Blueprint $table) {
            $table->unsignedTinyInteger('used_band')->nullable()->after('rating'); // 1 = a few, 2 = regularly, 3 = loads
        });
    }

    public function down(): void
    {
        Schema::table('piece_votes', function (Blueprint $table) {
            $table->dropColumn('used_band');
        });

        Schema::table('piece_votes', function (Blueprint $table) {
            $table->unsignedInteger('used_count')->default(0)->after('rating');
        });
    }
};
