// resources/js/composables/useCookieConsent.ts
// Shared state so the newsletter popup knows when cookie consent is resolved.

import { ref } from 'vue'

const hasResponded = ref(false)

// Check localStorage on first import
if (typeof window !== 'undefined') {
  const stored = localStorage.getItem('cookie-consent')
  if (stored) {
    hasResponded.value = true
  }
}

export function useCookieConsent() {
  function markResponded() {
    hasResponded.value = true
  }

  return {
    hasResponded,
    markResponded,
  }
}
