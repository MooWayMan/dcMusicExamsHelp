<?php

// database/migrations/2026_09_27_120000_add_former_to_contact_school.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A teacher who used to work at a school (Emily Bates and David Keeling at
 * Learn Music Ltd). The contact_school link is who works at a school; this
 * marks the ones who have left, so they stay on record without counting as
 * current staff. Existing links are all current.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_school', function (Blueprint $table) {
            $table->boolean('former')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('contact_school', function (Blueprint $table) {
            $table->dropColumn('former');
        });
    }
};
