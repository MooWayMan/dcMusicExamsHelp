<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SyncCalendarTasks
{
    /**
     * Sync Google Calendar REMINDER events on every admin request.
     * Throttled to once every 5 minutes via cache lock.
     *
     * Production-only — staging and local don't have Google OAuth
     * credentials configured, so firing the sync there just produces
     * noisy "Missing OAuth credentials" errors on every admin request.
     * See docs/dev-rules.md "Calendar sync" rule.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->environment('production')) {
            return $next($request);
        }

        if (Cache::add('gcal_sync_lock', true, 300)) {
            try {
                Artisan::call('calendar:sync-tasks');
            } catch (\Exception $e) {
                Log::warning('GCal sync middleware failed: ' . $e->getMessage());
            }
        }

        return $next($request);
    }
}
