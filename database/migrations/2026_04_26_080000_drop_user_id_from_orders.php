<?php

// database/migrations/2026_04_26_080000_drop_user_id_from_orders.php
//
// Final piece of the unified contacts refactor — drop the legacy `user_id`
// FK on orders. Replaced by `created_by_contact_id` (→ exam_contacts) which
// has been the source of truth since 25 Apr (Phase B step 10 backfill).
//
// Defensive: discover the FK constraint name at runtime and drop it
// (PostgreSQL gives differently-named constraints across environments),
// then drop the column.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'user_id')) {
            return;
        }

        $constraints = DB::select(<<<SQL
            SELECT tc.constraint_name
            FROM information_schema.table_constraints tc
            JOIN information_schema.key_column_usage kcu
              ON tc.constraint_name = kcu.constraint_name
             AND tc.table_schema = kcu.table_schema
            WHERE tc.table_name = 'orders'
              AND tc.constraint_type = 'FOREIGN KEY'
              AND kcu.column_name = 'user_id'
        SQL);

        foreach ($constraints as $row) {
            DB::statement('ALTER TABLE orders DROP CONSTRAINT "'.$row->constraint_name.'"');
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('orders', 'user_id')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
        });
    }
};
