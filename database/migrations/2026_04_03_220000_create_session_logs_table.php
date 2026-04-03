<?php

// database/migrations/2026_04_03_220000_create_session_logs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tracks daily working hours spent on the project.
     * Simple daily totals with optional notes.
     */
    public function up(): void
    {
        Schema::create('session_logs', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->decimal('hours', 4, 1); // e.g. 6.5
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('date'); // one entry per day
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_logs');
    }
};
