<?php

// database/migrations/2026_09_21_120000_create_site_stats_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Anonymous, cookie-free usage counts: one row per day per page (or per
 * page + action), holding only a running total. No visitor data of any
 * kind is stored — no IP, browser, user id or session. Written and read
 * only by App\Services\SiteStats.
 *
 * event and detail are NOT NULL with '' defaults on purpose: a unique
 * index treats NULLs as distinct, which would stop the upsert merging.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_stats', function (Blueprint $table) {
            $table->id();
            $table->date('day');
            $table->string('kind', 16);
            $table->string('path', 191);
            $table->string('event', 64)->default('');
            $table->string('detail', 100)->default('');
            $table->unsignedInteger('hits')->default(0);

            $table->unique(['day', 'kind', 'path', 'event', 'detail']);
            $table->index(['kind', 'day']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_stats');
    }
};
