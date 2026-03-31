<?php

// database/migrations/2026_03_31_100000_add_role_and_profile_to_users_table.php

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
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('teacher')->after('name'); // admin, teacher
            $table->string('phone')->nullable()->after('email');
            $table->text('notes')->nullable()->after('phone');
            $table->boolean('met_face_to_face')->default(false)->after('notes');
            $table->boolean('spoken_on_phone')->default(false)->after('met_face_to_face');
            $table->boolean('contacted_by_email')->default(false)->after('spoken_on_phone');
            $table->string('how_they_found_us')->nullable()->after('contacted_by_email');
            $table->string('hubspot_contact_id')->nullable()->after('how_they_found_us');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role',
                'phone',
                'notes',
                'met_face_to_face',
                'spoken_on_phone',
                'contacted_by_email',
                'how_they_found_us',
                'hubspot_contact_id',
            ]);
        });
    }
};
