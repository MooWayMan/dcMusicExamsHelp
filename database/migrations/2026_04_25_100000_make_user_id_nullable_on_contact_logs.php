<?php

// database/migrations/2026_04_25_100000_make_user_id_nullable_on_contact_logs.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Phase D: contact_logs now references exam_contact_id (the unified
     * model). user_id stays for now as a legacy column but becomes nullable
     * so the new code path can write logs without a User row. user_id will
     * be dropped entirely in a later migration once it's verifiably unused.
     */
    public function up(): void
    {
        Schema::table('contact_logs', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('contact_logs', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
