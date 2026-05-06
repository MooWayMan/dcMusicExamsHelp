<!-- resources/js/components/CookieConsent.vue -->
<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { Cookie } from 'lucide-vue-next'
import { router } from '@inertiajs/vue3'
import { useCookieConsent } from '@/composables/useCookieConsent'

const { accept: acceptConsent, decline: declineConsent } = useCookieConsent()
const isVisible = ref(false)

// Pages where the banner must NOT auto-popup, otherwise it covers
// the very content the user came to read before deciding.
// These pages provide their own inline Accept / Decline buttons.
const SUPPRESS_AUTO_POPUP_PATHS = ['/cookies', '/privacy']

let pendingShow: ReturnType<typeof setTimeout> | null = null

function clearPending() {
  if (pendingShow) {
    clearTimeout(pendingShow)
    pendingShow = null
  }
}

function evaluateBanner(path: string) {
  const onSuppressedPage = SUPPRESS_AUTO_POPUP_PATHS.includes(path)
  const hasConsent = !!localStorage.getItem('cookie-consent')

  // Already decided — never auto-show again
  if (hasConsent) return

  // On the policy pages — keep banner hidden so user can read.
  // The page itself shows inline Accept/Decline buttons.
  if (onSuppressedPage) {
    clearPending()
    isVisible.value = false
    return
  }

  // Not on a suppressed page, no consent yet — schedule the banner.
  // Skip if it's already visible or already scheduled.
  if (isVisible.value || pendingShow) return
  pendingShow = setTimeout(() => {
    isVisible.value = true
    pendingShow = null
  }, 1500)
}

let removeNavigateListener: (() => void) | null = null

onMounted(() => {
  // First page load
  evaluateBanner(window.location.pathname)

  // Re-evaluate on every Inertia SPA navigation, since this component
  // is mounted once at the app root and onMounted only fires once.
  removeNavigateListener = router.on('navigate', (event) => {
    const url = new URL(event.detail.page.url, window.location.origin)
    evaluateBanner(url.pathname)
  })

  // Listen for footer "Cookie Preferences" link — always honoured,
  // even on the cookie policy page (user explicitly asked for it)
  window.addEventListener('open-cookie-preferences', openFromFooter)
})

onBeforeUnmount(() => {
  clearPending()
  if (removeNavigateListener) removeNavigateListener()
  window.removeEventListener('open-cookie-preferences', openFromFooter)
})

function openFromFooter() {
  clearPending()
  isVisible.value = true
}

function accept() {
  clearPending()
  acceptConsent()
  isVisible.value = false
}

function decline() {
  clearPending()
  declineConsent()
  isVisible.value = false
}
</script>

<template>
  <Transition
    enter-from-class="opacity-0"
    enter-active-class="transition duration-500 ease-out"
    enter-to-class="opacity-100"
    leave-from-class="opacity-100"
    leave-active-class="transition duration-300 ease-in"
    leave-to-class="opacity-0"
  >
    <!-- Backdrop overlay — blocks interaction with the page -->
    <div
      v-if="isVisible"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4"
    >
      <!-- Popup card -->
      <div class="w-full max-w-sm rounded-2xl bg-brand-surface p-6 shadow-2xl ring-1 ring-brand-border sm:p-8">
        <div class="flex flex-col items-center text-center">
          <!-- Cookie icon -->
          <div class="mb-4 rounded-full bg-brand-accent/10 p-3">
            <Cookie class="h-8 w-8 text-brand-accent" />
          </div>

          <h3 class="text-lg font-bold text-brand-text sm:text-xl">
            We use cookies
          </h3>
          <p class="mt-2 text-base leading-relaxed text-brand-text-soft sm:text-base">
            We use cookies to improve your experience and understand how our site is used.
          </p>

          <!-- Accept all — prominent -->
          <button
            @click="accept"
            class="mt-5 w-full cursor-pointer rounded-lg bg-brand-accent px-6 py-3 text-base font-semibold text-brand-text-inverse transition-colors hover:bg-brand-accent-dark sm:text-lg"
          >
            Accept all cookies
          </button>

          <!-- Necessary only + Cookie policy — smaller, underneath -->
          <div class="mt-3 flex items-center justify-center gap-4">
            <button
              @click="decline"
              class="cursor-pointer text-base text-brand-text-soft transition-colors hover:text-brand-accent hover:underline"
            >
              Accept only necessary cookies
            </button>
            <span class="text-brand-text-soft/40">|</span>
            <a
              href="/cookies"
              class="text-base text-brand-text-soft transition-colors hover:text-brand-accent hover:underline"
            >
              Cookie Policy
            </a>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>
