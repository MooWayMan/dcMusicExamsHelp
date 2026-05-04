<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin viewer for the subscribers list. Shows everyone who has signed
 * up via /subscribe or the /lead-magnet/subscribe lead-magnet form, with
 * filters for source, search, and marketing-consent.
 *
 * Eager-loads matching User accounts by email so admin can see at a
 * glance which subscribers have already registered for an account.
 */
class SubscriberController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $source = $request->input('source');
        $marketingConsent = $request->input('marketing_consent'); // 'yes' | 'no' | null

        $query = Subscriber::query();

        if ($search) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        if ($source) {
            $query->where('source', $source);
        }

        if ($marketingConsent === 'yes') {
            $query->whereNotNull('marketing_consent_at');
        } elseif ($marketingConsent === 'no') {
            $query->whereNull('marketing_consent_at');
        }

        $subscribers = $query
            ->orderByDesc('subscribed_at')
            ->paginate(20)
            ->withQueryString();

        // Build a single-pass map of users keyed by lowercase email so we
        // can mark each subscriber as "has user account" without N+1
        // queries. Postgres ilike means case is irrelevant; lowercasing
        // the keys keeps the lookup the same shape.
        $emails = collect($subscribers->items())->pluck('email')->map(fn ($e) => strtolower((string) $e))->all();
        $userMap = empty($emails)
            ? collect()
            : User::query()
                ->whereIn(DB::raw('lower(email)'), $emails)
                ->get(['id', 'name', 'email'])
                ->keyBy(fn (User $u) => strtolower($u->email));

        $subscribers->through(function (Subscriber $s) use ($userMap) {
            $linkedUser = $userMap->get(strtolower((string) $s->email));

            return [
                'id' => $s->id,
                'name' => $s->name,
                'email' => $s->email,
                'role' => $s->role,
                'source' => $s->source,
                'subscribed_at' => $s->subscribed_at?->format('d M Y'),
                'unsubscribed_at' => $s->unsubscribed_at?->format('d M Y'),
                'marketing_consent_at' => $s->marketing_consent_at?->format('d M Y'),
                'has_marketing_consent' => ! is_null($s->marketing_consent_at),
                'linked_user' => $linkedUser ? [
                    'id' => $linkedUser->id,
                    'name' => $linkedUser->name,
                ] : null,
            ];
        });

        // Distinct sources, used to populate the filter dropdown so it
        // stays in sync with whatever has been written to the table.
        $sources = Subscriber::query()
            ->select('source')
            ->distinct()
            ->orderBy('source')
            ->pluck('source')
            ->filter()
            ->values()
            ->all();

        $summary = [
            'total' => Subscriber::count(),
            'active' => Subscriber::whereNull('unsubscribed_at')->count(),
            'marketing_consented' => Subscriber::whereNotNull('marketing_consent_at')->count(),
            'lead_magnet' => Subscriber::where('source', 'trinity_checklist')->count(),
        ];

        return Inertia::render('admin/Subscribers/Index', [
            'subscribers' => $subscribers,
            'summary' => $summary,
            'sources' => $sources,
            'filters' => [
                'search' => $search,
                'source' => $source,
                'marketing_consent' => $marketingConsent,
            ],
        ]);
    }
}
