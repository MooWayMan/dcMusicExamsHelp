// resources/js/composables/useAnalytics.ts
//
// Single helper for firing GA4 + Meta Pixel events. Used by the paid-traffic
// landing page (`SwitchToCentre120`), the lead-magnet form
// (`LeadMagnetCapture`) and the `BookingModal` to fire conversion events
// that Google Ads imports as conversions AND that Meta uses for audience
// optimisation and lookalike training.
//
// Why a wrapper instead of calling `window.gtag` / `window.fbq` directly:
//   1. Consent gating — both globals only exist if the visitor accepted
//      cookies (see `useCookieConsent.ts` + `app.blade.php`). Calling them
//      unconditionally throws a console error for non-consenters.
//   2. Type safety — keeps `as any` casts out of every call site.
//   3. Single place to add new tracking platforms (Reddit Pixel, TikTok, etc).
//
// GA4 → Google Ads link in place (May 10 2026 confirmation email).
// Meta Pixel ID 2164549404093546 = musicExams.help website dataset
// (live ad account 26629640546692642, NOT the personal one).

type EventParams = Record<string, string | number | boolean>

declare global {
  interface Window {
    gtag?: (...args: unknown[]) => void
    fbq?: (...args: unknown[]) => void
  }
}

// Map our internal event names to Meta's standard events. Standard events
// are what Meta's algorithm understands for audience optimisation — using
// a custom event name means Meta can't optimise as effectively. Any event
// name not in this map falls back to `trackCustom` (still recorded, just
// less powerful for bidding).
const META_EVENT_MAP: Record<string, string> = {
  lead_form_submit: 'Lead',
  booking_click: 'InitiateCheckout',
}

export function useAnalytics() {
  function trackEvent(name: string, params: EventParams = {}) {
    if (typeof window === 'undefined') return

    // Default GBP currency on monetary events — keeps Google Ads value
    // reporting consistent. Override by passing `currency` in params.
    const payload: EventParams = {
      currency: 'GBP',
      ...params,
    }

    // Fire GA4 event
    if (typeof window.gtag === 'function') {
      try {
        window.gtag('event', name, payload)
      } catch {
        // Never let analytics failure break user-facing flow.
      }
    }

    // Fire Meta Pixel event (using the standard event name if mapped,
    // else fall back to a custom event of the same name).
    if (typeof window.fbq === 'function') {
      try {
        const metaEventName = META_EVENT_MAP[name]
        if (metaEventName) {
          window.fbq('track', metaEventName, payload)
        } else {
          window.fbq('trackCustom', name, payload)
        }
      } catch {
        // Never let analytics failure break user-facing flow.
      }
    }
  }

  return { trackEvent }
}
