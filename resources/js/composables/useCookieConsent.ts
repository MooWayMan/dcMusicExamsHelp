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
  // Load Google Analytics now that consent is given
  const s = document.createElement('script')
  s.async = true
  s.src = 'https://www.googletagmanager.com/gtag/js?id=G-TZJ8ZCZW3W'
  document.head.appendChild(s)
  ;(window as any).dataLayer = (window as any).dataLayer || []
  function gtag(...args: any[]) { (window as any).dataLayer.push(args) }
  gtag('js', new Date())
  gtag('config', 'G-TZJ8ZCZW3W')
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
