<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class ComingSoon
{
    /**
     * If COMING_SOON=true, show the coming soon page for all public routes.
     * Admin/auth routes are excluded so Paul can still log in.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('app.coming_soon')) {
            return $next($request);
        }

        // Always allow these paths through (admin, auth, login, API, etc.)
        $allowed = [
            'login',
            'register',
            'auth/*',
            'dashboard',
            'admin/*',
            'coming-soon',
            '_ignition/*',
        ];

        foreach ($allowed as $pattern) {
            if ($request->is($pattern)) {
                return $next($request);
            }
        }

        // Show coming soon page for everything else
        return Inertia::render('ComingSoonPage')->toResponse($request);
    }
}
