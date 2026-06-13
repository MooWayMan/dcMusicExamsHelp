<?php

// database/migrations/2026_06_13_210000_create_school_instrument_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Instruments a school is associated with, persisted so they survive
     * deletion of the underlying exam entries. Mirrors contact_instrument.
     */
    public function up(): void
    {
        Schema::create('school_instrument', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->foreignId('instrument_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['school_id', 'instrument_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_instrument');
    }
};
