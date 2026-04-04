// resources/js/composables/useSubscription.ts
// Shared state across all email capture components.
// When one form subscribes, all others react instantly.

import { ref } from 'vue'

const isSubscribed = ref(false)
const subscriberName = ref('')

// Check localStorage on first import
if (typeof window !== 'undefined') {
  const stored = localStorage.getItem('subscribed')
  if (stored) {
    isSubscribed.value = true
    subscriberName.value = localStorage.getItem('subscriber-name') || ''
  }
}

export function useSubscription() {
  function markSubscribed(name: string) {
    isSubscribed.value = true
    subscriberName.value = name
    localStorage.setItem('subscribed', 'true')
    localStorage.setItem('subscriber-name', name)
  }

  return {
    isSubscribed,
    subscriberName,
    markSubscribed,
  }
}
