<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records the result of auto-syncing a consented subscriber into HubSpot
 * (App\Jobs\SyncSubscriberToHubSpot):
 *
 *   - hubspot_contact_id — the HubSpot object id returned by the upsert, so
 *     later updates (e.g. a consent withdrawal) target the same contact and
 *     we can tell who has already reached the CRM.
 *   - hubspot_synced_at  — when the last successful push happened.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscribers', function (Blueprint $table) {
            $table->string('hubspot_contact_id')->nullable();
            $table->timestamp('hubspot_synced_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('subscribers', function (Blueprint $table) {
            $table->dropColumn(['hubspot_contact_id', 'hubspot_synced_at']);
        });
    }
};
