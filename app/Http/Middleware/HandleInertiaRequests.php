<?php

namespace App\Http\Middleware;

use App\Models\PageMaintenance;
use App\Services\Impersonation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        // Surface impersonation state to the frontend so the sitewide banner
        // (resources/js/components/ImpersonationBanner.vue) can render and
        // offer the "Return to admin" button. Resolved lazily so we don't
        // touch the DB on every request when no impersonation is in flight.
        $impersonation = app(Impersonation::class);

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                'impersonating' => fn () => $impersonation->isImpersonating()
                    ? [
                        'admin_name' => $impersonation->originalAdmin()?->name,
                        'target_name' => $request->user()?->name,
                    ]
                    : null,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'batch_result' => fn () => $request->session()->get('batch_result'),
            ],
            'maintenancePages' => fn () => Schema::hasTable('page_maintenance')
                ? PageMaintenance::where('is_active', true)->pluck('message', 'page_slug')->toArray()
                : [],
        ];
    }
}
