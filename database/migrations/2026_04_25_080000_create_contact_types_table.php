<?php

// database/migrations/2026_04_25_080000_create_contact_types_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Multi-type pivot: one human can be teacher AND parent AND subscriber.
     * Replaces the single `role` column on exam_contacts.
     *
     * Allowed type values (enforced at app layer for now):
     *   teacher | parent | candidate | school_admin | trinity_admin | subscriber
     */
    public function up(): void
    {
        Schema::create('contact_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_contact_id')->constrained()->onDelete('cascade');
            $table->string('type');
            $table->timestamps();

            $table->unique(['exam_contact_id', 'type']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_types');
    }
};
