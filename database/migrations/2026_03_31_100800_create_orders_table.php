<?php

// database/migrations/2026_03_31_100800_create_orders_table.php

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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // teacher
            $table->foreignId('school_id')->nullable()->constrained()->onDelete('set null'); // venue/school
            $table->string('trinity_order_number')->unique(); // e.g. 1-15899713974
            $table->string('delivery_method'); // Digital, Default (F2F)
            $table->string('subject_area'); // Music, Rock and Pop
            $table->integer('candidates')->default(1);
            $table->string('venue')->nullable(); // from Trinity export
            $table->string('order_status'); // Ready to Deliver, Delivered, Processed
            $table->date('requested_start_date');
            $table->decimal('commission_rate', 5, 2)->nullable(); // 20.00 for DG, 28.00 for F2F
            $table->decimal('commission_amount', 8, 2)->nullable(); // calculated amount
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
