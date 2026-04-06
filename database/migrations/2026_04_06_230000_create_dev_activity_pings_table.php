<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tracks localhost page hits for automatic session hour calculation.
     * Only records in local environment — never on production.
     */
    public function up(): void
    {
        Schema::create('dev_activity_pings', function (Blueprint $table) {
            $table->id();
            $table->timestamp('pinged_at');
            $table->string('path', 500)->nullable(); // which page was hit
            $table->index('pinged_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dev_activity_pings');
    }
};
