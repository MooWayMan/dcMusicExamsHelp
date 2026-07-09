<?php

// database/migrations/2026_07_09_120000_create_contact_merge_dismissals_table.php

use App\Models\ExamContact;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remembers "not the same person" decisions so a dismissed duplicate pair is
 * never flagged again. The pair is stored order-independently (low id / high
 * id) with a unique index so (A,B) and (B,A) can't both exist.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_merge_dismissals', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(ExamContact::class, 'low_contact_id')
                ->constrained('exam_contacts')
                ->cascadeOnDelete();

            $table->foreignIdFor(ExamContact::class, 'high_contact_id')
                ->constrained('exam_contacts')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['low_contact_id', 'high_contact_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_merge_dismissals');
    }
};
