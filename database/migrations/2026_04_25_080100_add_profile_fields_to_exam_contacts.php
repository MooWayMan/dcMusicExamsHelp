<?php

// database/migrations/2026_04_25_080100_add_profile_fields_to_exam_contacts.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Brings the rich teacher profile fields (currently on `users`) onto
     * `exam_contacts` so that exam_contacts becomes the single source of
     * truth for any human in the system.
     */
    public function up(): void
    {
        Schema::table('exam_contacts', function (Blueprint $table) {
            $table->string('how_they_found_us')->nullable()->after('notes');
            $table->string('hubspot_contact_id')->nullable()->after('how_they_found_us');
            $table->boolean('met_face_to_face')->default(false)->after('hubspot_contact_id');
            $table->boolean('spoken_on_phone')->default(false)->after('met_face_to_face');
            $table->boolean('contacted_by_email')->default(false)->after('spoken_on_phone');
        });
    }

    public function down(): void
    {
        Schema::table('exam_contacts', function (Blueprint $table) {
            $table->dropColumn([
                'how_they_found_us',
                'hubspot_contact_id',
                'met_face_to_face',
                'spoken_on_phone',
                'contacted_by_email',
            ]);
        });
    }
};
