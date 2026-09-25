<?php

// database/migrations/2026_09_25_220000_add_sent_at_to_prize_workflow.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When the winner email went out. The 12 months a winner has to use their gift
 * token run from the day they were told, so a winner told late is never
 * backdated. Rows ticked before this existed stay null and the Prizes list
 * falls back to the award date for them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prize_workflow', function (Blueprint $table) {
            $table->timestamp('sent_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('prize_workflow', function (Blueprint $table) {
            $table->dropColumn('sent_at');
        });
    }
};
