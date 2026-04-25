<?php

// database/migrations/2026_04_25_080200_create_contact_school_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Mirrors `teacher_school` but for exam_contacts. Allows any contact
     * (teacher, school_admin, etc.) to be linked to one or more schools.
     */
    public function up(): void
    {
        Schema::create('contact_school', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_contact_id')->constrained()->onDelete('cascade');
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['exam_contact_id', 'school_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_school');
    }
};
