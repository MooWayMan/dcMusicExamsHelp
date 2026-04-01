<?php

// database/migrations/2026_04_01_140000_create_tasks_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Task management for launch readiness and ongoing admin work.
     * Supports priorities (high/medium/low), categories, and completion tracking.
     */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('detail')->nullable();
            $table->string('priority')->default('medium'); // high, medium, low
            $table->string('status')->default('pending'); // pending, in_progress, completed
            $table->string('assigned_to')->default('Paul'); // Paul, Spider-Man, Paul + SM
            $table->string('category')->nullable(); // launch, admin, content, marketing, etc.
            $table->integer('sort_order')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
