// resources/js/composables/useAnalytics.ts
//
// Single helper for firing GA4 events. Used by the paid-traffic landing
// page (`SwitchToCentre120`) and the BookingModal to fire conversion
// events that Google Ads imports as conversions.
//
// Why a wrapper instead of calling `window.gtag` directly:
//   1. Consent gating — `window.gtag` only exists if the visitor accepted
//      cookies (see `useCookieConsent.ts` + `app.blade.php`). Calling it
//      unconditionally throws a console error for non-consenters.
//   2. Type safety — keeps `as any` casts out of every call site.
//   3. Single place to add Meta Pixel / Reddit Pixel later (Phase 2).
//
// GA4 → Google Ads link is already in place (May 10 2026 confirmation
// email from noreply-analytics@google.com). Once we create conversion
// actions in Google Ads, we'll import these events as conversions.

type EventParams = Record<string, string | number | boolean>

declare global {
  interface Window {
    gtag?: (...args: unknown[]) => void
  }
}

export function useAnalytics() {
  function trackEvent(name: string, params: EventParams = {}) {
    if (typeof window === 'undefined') return
    if (typeof window.gtag !== 'function') return // consent declined or GA4 not loaded

    // Default GBP currency on monetary events — keeps Google Ads value
    // reporting consistent. Override by passing `currency` in params.
    const payload: EventParams = {
      currency: 'GBP',
      ...params,
    }

    try {
      window.gtag('event', name, payload)
    } catch {
      // Never let analytics failure break user-facing flow.
    }
  }

  return { trackEvent }
}
