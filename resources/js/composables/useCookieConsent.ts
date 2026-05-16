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

function loadAnalytics() {
  loadGoogleAnalytics()
  loadMetaPixel()
}

function loadGoogleAnalytics() {
  const s = document.createElement('script')
  s.async = true
  s.src = 'https://www.googletagmanager.com/gtag/js?id=G-TZJ8ZCZW3W'
  document.head.appendChild(s)
  ;(window as any).dataLayer = (window as any).dataLayer || []
  function gtag(...args: any[]) { (window as any).dataLayer.push(args) }
  gtag('js', new Date())
  gtag('config', 'G-TZJ8ZCZW3W')
  ;(window as any).gtag = gtag
}

function loadMetaPixel() {
  // Standard Meta Pixel snippet, wrapped to load on consent.
  // Pixel ID 2164549404093546 = musicExams.help website dataset
  // (live ad account 26629640546692642, NOT the personal one).
  // Matches the inline script in app.blade.php for returning visitors;
  // the `if (w.fbq) return` guard inside the snippet prevents double init.
  const w = window as any
  if (w.fbq) return

  const n = w.fbq = function (...args: any[]) {
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
    loadAnalytics()
  }

  function decline() {
    localStorage.setItem('cookie-consent', 'declined')
    currentChoice.value = 'declined'
    hasResponded.value = true
  }

  return {
    hasResponded,
    currentChoice,
    markResponded,
    accept,
    decline,
  }
}
