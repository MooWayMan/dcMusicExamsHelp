<?php

namespace App\Http\Controllers;

use App\Jobs\SyncSubscriberToHubSpot;
use App\Mail\LeadMagnetDelivery;
use App\Models\Subscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SubscriberController extends Controller
{
    /**
     * Store a new subscriber (AJAX endpoint).
     */
    public function store(Request $request): JsonResponse
    {
        // Honeypot — silently swallow bot submissions. `website_url` is a
        // hidden form field a real visitor never fills. We return the same
        // success shape so bots can't tell it's a trap. See dev-rules.md
        // "Public forms" rule.
        if (filled($request->input('website_url'))) {
            return response()->json([
                'success' => true,
                'message' => 'Thank you for subscribing!',
            ]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'role' => 'nullable|string|in:teacher,parent,student',
            'source' => 'nullable|string|max:50',
            'website_url' => 'nullable|string|max:255',
        ]);

        // Check if already subscribed
        $existing = Subscriber::where('email', $validated['email'])->first();

        if ($existing) {
            // If they unsubscribed before, re-subscribe them
            if ($existing->unsubscribed_at) {
                $existing->update([
                    'name' => $validated['name'],
                    'role' => $validated['role'] ?? $existing->role,
                    'unsubscribed_at' => null,
                    'subscribed_at' => now(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Welcome back! You have been re-subscribed.',
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'You are already subscribed. Thank you!',
            ]);
        }

        Subscriber::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'] ?? null,
            'source' => $validated['source'] ?? 'website',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thank you for subscribing!',
        ]);
    }

    /**
     * Lead magnet entry-point: capture name + email, optionally record a
     * marketing-consent timestamp, and email the subscriber the Trinity
     * Exam Checklist PDF as a transactional email.
     *
     * Source is hard-coded to `trinity_checklist` so admin reports can
     * always see where the subscriber came from. The original /subscribe
     * endpoint above is kept untouched for any other forms still hitting it.
     */
    public function leadMagnet(Request $request): JsonResponse
    {
        // Honeypot — silently swallow bot submissions. Particularly important
        // here because a successful POST triggers a real email send (the
        // PDF attachment) — an unprotected endpoint is a free email-bombing
        // tool. See dev-rules.md "Public forms" rule.
        if (filled($request->input('website_url'))) {
            return response()->json([
                'success' => true,
                'message' => 'Thanks — your Trinity Exam Checklist is on its way.',
            ]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'marketing_consent' => 'nullable|boolean',
            'website_url' => 'nullable|string|max:255',
        ]);

        $marketingConsent = (bool) ($validated['marketing_consent'] ?? false);

        $subscriber = Subscriber::firstOrNew(['email' => $validated['email']]);
        $subscriber->fill([
            'name' => $validated['name'],
            'source' => 'trinity_checklist',
        ]);

        // Re-subscribe an unsubscribed person (lead magnet sign-up implies
        // current interest). Only stamp marketing consent if explicitly
        // opted-in — silence is NOT consent under GDPR.
        if (! $subscriber->exists || $subscriber->unsubscribed_at) {
            $subscriber->subscribed_at = now();
            $subscriber->unsubscribed_at = null;
        }

        if ($marketingConsent && ! $subscriber->marketing_consent_at) {
            $subscriber->marketing_consent_at = now();
        }

        $subscriber->save();

        if ($marketingConsent) {
            SyncSubscriberToHubSpot::dispatch($subscriber);
        }

        // Deliver the PDF. Failure is logged but doesn't block the
        // success response — Paul can re-trigger from the admin panel
        // if the bounce rate ever spikes.
        try {
            Mail::to($subscriber->email)->send(
                new LeadMagnetDelivery(subscriberName: $subscriber->name)
            );
        } catch (\Exception $e) {
            Log::error('LeadMagnetDelivery email failed: '.$e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => "Thanks {$subscriber->name} — your Trinity Exam Checklist is on its way.",
        ]);
    }
}
