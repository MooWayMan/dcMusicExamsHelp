<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Jobs\SyncSubscriberToHubSpot;
use App\Models\Subscriber;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        $subscriber = Subscriber::where('email', $request->user()->email)->first();

        return Inertia::render('settings/Profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
            'marketingConsent' => (bool) $subscriber?->marketing_consent_at,
        ]);
    }

    /**
     * Update the user's marketing email preferences.
     *
     * Consent lives on the matching `subscribers` row (keyed by email), so a
     * user and their marketing consent stay in one place. Withdrawing consent
     * simply clears the timestamp — we keep the subscriber row and their active
     * subscription intact rather than deleting anything.
     */
    public function updateEmailPreferences(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'marketing_consent' => ['nullable', 'boolean'],
        ]);

        $wants = (bool) ($validated['marketing_consent'] ?? false);
        $user = $request->user();

        $subscriber = Subscriber::firstOrNew(['email' => $user->email]);

        if (! $subscriber->exists) {
            $subscriber->name = $user->name;
            $subscriber->source = 'account_settings';
            $subscriber->subscribed_at = now();
        }

        $subscriber->marketing_consent_at = $wants
            ? ($subscriber->marketing_consent_at ?? now())
            : null;

        $subscriber->save();

        // Mirror the change into HubSpot — opt-in adds them to the marketing
        // list, opt-out removes them. The job self-guards (only touches HubSpot
        // when there's something to sync).
        SyncSubscriberToHubSpot::dispatch($subscriber);

        return to_route('profile.edit');
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return to_route('profile.edit');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
