<?php

// database/migrations/2026_03_31_100300_create_subject_areas_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subject_areas', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Music (Classical), Rock and Pop, Jazz
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subject_areas');
    }
};
