<?php

// database/migrations/2026_04_21_210000_add_commission_paid_fields_to_orders_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds Trinity commission payment tracking fields to orders.
     * `commission_paid_at` is the remittance date from the Trinity Finance PDF.
     * `commission_paid_amount` is the net amount actually paid (may differ from
     * `commission_amount` for F2F orders that accrue multiple adjustments).
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->date('commission_paid_at')->nullable()->after('commission_amount');
            $table->decimal('commission_paid_amount', 8, 2)->nullable()->after('commission_paid_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['commission_paid_at', 'commission_paid_amount']);
        });
    }
};
