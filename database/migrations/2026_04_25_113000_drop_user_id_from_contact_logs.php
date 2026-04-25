<?php

// database/migrations/2026_04_25_113000_drop_user_id_from_contact_logs.php
//
// Phase D-3, Step 4 — drop the legacy `user_id` FK on contact_logs.
// Replaced by `exam_contact_id` (→ exam_contacts) when the unified
// contacts model shipped. The column is empty on prod.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('contact_logs', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->nullOnDelete();
        });
    }
};
