<?php

// database/migrations/2026_04_25_114000_drop_contact_name_and_phone_from_schools.php
//
// Phase D-3, Step 5 — drop the denormalised `schools.contact_name` and
// `schools.phone` columns. The unified contacts model owns these now via
// the `contact_school` pivot (ExamContact ↔ School), and the school's
// "primary contact" is computed in SchoolController::pickPrimarySchoolContact.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn(['contact_name', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->string('phone')->nullable();
            $table->string('contact_name')->nullable();
        });
    }
};
