<?php

namespace App\Http\Middleware;

use App\Models\DevActivityPing;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Silently logs a timestamp when the local dev site is hit.
 * Throttled to max 1 ping per minute to avoid spamming the DB.
 * Only active in the 'local' environment — does nothing on production.
 */
class TrackDevActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('local') && ! Cache::has('dev_activity_throttle')) {
            DevActivityPing::create([
                'pinged_at' => now(),
                'path' => $request->path(),
            ]);

            // Throttle: ignore further pings for 60 seconds
            Cache::put('dev_activity_throttle', true, 60);
        }

        return $next($request);
    }
}
