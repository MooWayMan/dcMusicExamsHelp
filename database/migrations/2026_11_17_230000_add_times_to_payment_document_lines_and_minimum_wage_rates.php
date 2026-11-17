<?php

// database/migrations/2026_11_17_230000_add_times_to_payment_document_lines_and_minimum_wage_rates.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Hourly lines on a payment document (a steward) get a start and an end time,
// "HH:MM", and the edit page works the hours out from them.
//
// minimum_wage_rates holds the UK National Living Wage (aged 21 and over) by
// the date each rate starts. App\Services\MinimumWage reads GOV.UK and adds or
// corrects rows; GOV.UK only lists the current rates, so this table is also
// the history for documents dated in earlier years. The rows below are the
// rates as published on GOV.UK on 17 Nov 2026.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_document_lines', function (Blueprint $table) {
            $table->string('start_time', 5)->nullable()->after('line_date');
            $table->string('end_time', 5)->nullable()->after('start_time');
        });

        Schema::create('minimum_wage_rates', function (Blueprint $table) {
            $table->id();
            $table->date('starts_on')->unique();
            $table->decimal('rate', 6, 2);
            $table->timestamps();
        });

        $now = now();
        DB::table('minimum_wage_rates')->insert([
            ['starts_on' => '2024-04-01', 'rate' => '11.44', 'created_at' => $now, 'updated_at' => $now],
            ['starts_on' => '2025-04-01', 'rate' => '12.21', 'created_at' => $now, 'updated_at' => $now],
            ['starts_on' => '2026-04-01', 'rate' => '12.71', 'created_at' => $now, 'updated_at' => $now],
            ['starts_on' => '2027-04-01', 'rate' => '13.21', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('minimum_wage_rates');

        Schema::table('payment_document_lines', function (Blueprint $table) {
            $table->dropColumn(['start_time', 'end_time']);
        });
    }
};
