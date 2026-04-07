<?php

// database/migrations/2026_04_07_095400_add_show_on_thank_you_to_exam_entries_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_entries', function (Blueprint $table) {
            $table->boolean('show_on_thank_you')->default(true)->after('show_full_name');
        });
    }

    public function down(): void
    {
        Schema::table('exam_entries', function (Blueprint $table) {
            $table->dropColumn('show_on_thank_you');
        });
    }
};
