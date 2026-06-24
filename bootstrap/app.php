<?php

use App\Http\Middleware\ComingSoon;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\TrackDevActivity;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            ComingSoon::class,
            TrackDevActivity::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Session-expiry safety net for non-GET requests (ported from the
        // MusicRegisterOnline "PATCH /login" incident).
        //
        // Browsers preserve the verb on a 302, so a PATCH/PUT/DELETE fired
        // after the session died would follow the auth redirect AS a PATCH to
        // /login and blow up with MethodNotAllowedHttpException. A 303 forces
        // the browser to re-issue the follow-up as GET. We also turn a 419
        // Page Expired into a quiet redirect back with a flash message instead
        // of the raw error page.
        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            $isWriteVerb = in_array($request->getMethod(), ['PUT', 'PATCH', 'DELETE'], true);

            if ($response->getStatusCode() === 419) {
                $redirect = back()->with('error', 'Your session has expired. Please try again.');

                return $isWriteVerb ? $redirect->setStatusCode(303) : $redirect;
            }

            if ($isWriteVerb && $response->isRedirect()) {
                $response->setStatusCode(303);
            }

            return $response;
        });
    })->create();
