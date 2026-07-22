// resources/js/composables/useCookieConsent.ts
// Single source of truth for cookie consent state. Used by:
//  - CookieConsent.vue (the popup banner)
//  - CookiePolicy.vue (inline accept/decline buttons on the policy page)
//  - NewsletterPopup.vue (waits until consent has been resolved)

import { ref } from 'vue'

type ConsentValue = 'accepted' | 'declined' | null

const hasResponded = ref(false)
const currentChoice = ref<ConsentValue>(null)

// Check localStorage on first import
if (typeof window !== 'undefined') {
  const stored = localStorage.getItem('cookie-consent') as ConsentValue
  if (stored === 'accepted' || stored === 'declined') {
    hasResponded.value = true
    currentChoice.value = stored
  }
}

// Google Consent Mode v2.
// gtag.js is now loaded on every page by the bootstrap in app.blade.php with
// consent DEFAULTING to denied (cookieless pings only). We therefore no
// longer (re)load the library here — we just flip the consent state via
// gtag('consent', 'update', ...). Meta has no equivalent modelling, so the
// Meta Pixel stays hard-gated: it only loads once the visitor accepts.
function updateGoogleConsent(state: 'granted' | 'denied') {
  const w = window as any
  if (typeof w.gtag !== 'function') return
  w.gtag('consent', 'update', {
    ad_storage: state,
    ad_user_data: state,
    ad_personalization: state,
    analytics_storage: state,
  })
  // Once granted we no longer need to redact ad identifiers.
  if (state === 'granted') w.gtag('set', 'ads_data_redaction', false)
}

function loadMetaPixel() {
  // Standard Meta Pixel snippet, wrapped to load on consent.
  // Pixel ID 2164549404093546 = musicExams.help website dataset
  // (live ad account 26629640546692642, NOT the personal one).
  // Matches the inline script in app.blade.php for returning visitors;
  // the `if (w.fbq) return` guard inside the snippet prevents double init.
  const w = window as any
  // Tracking is production-only (see app.blade.php). Off-production the pixel
  // must never load, even on an explicit accept.
  if (w.__TRACKING_ENABLED__ === false) return
  if (w.fbq) return

  const n: any = w.fbq = function (...args: any[]) {
    n.callMethod ? n.callMethod.apply(n, args) : n.queue.push(args)
  }
  if (!w._fbq) w._fbq = n
  n.push = n
  n.loaded = true
  n.version = '2.0'
  n.queue = []

  const t = document.createElement('script')
  t.async = true
  t.src = 'https://connect.facebook.net/en_US/fbevents.js'
  const s = document.getElementsByTagName('script')[0]
  s.parentNode?.insertBefore(t, s)

  w.fbq('init', '2164549404093546')
  w.fbq('track', 'PageView')
}

export function useCookieConsent() {
  function markResponded() {
    hasResponded.value = true
  }

  function accept() {
    localStorage.setItem('cookie-consent', 'accepted')
    currentChoice.value = 'accepted'
    hasResponded.value = true
    // Grant Google consent (gtag is already on the page in denied mode),
    // then load the Meta Pixel, which is still gated on explicit accept.
    updateGoogleConsent('granted')
    loadMetaPixel()
  }

  function decline() {
    localStorage.setItem('cookie-consent', 'declined')
    currentChoice.value = 'declined'
    hasResponded.value = true
    // Explicitly confirm denied so the choice is recorded for modelling.
    // (Default is already denied; Meta is never loaded.)
    updateGoogleConsent('denied')
  }

  return {
    hasResponded,
    currentChoice,
    markResponded,
    accept,
    decline,
  }
}
