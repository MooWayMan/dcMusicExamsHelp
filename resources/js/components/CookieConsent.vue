<!-- resources/js/components/CookieConsent.vue -->
<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { Cookie } from 'lucide-vue-next'
import { router } from '@inertiajs/vue3'
import { useCookieConsent } from '@/composables/useCookieConsent'

const { accept: acceptConsent, decline: declineConsent } = useCookieConsent()
const isVisible = ref(false)
const bannerStyle = ref<'modal' | 'bottom-bar'>('modal')

// Pages where the banner must NOT auto-popup, otherwise it covers
// the very content the user came to read before deciding.
// These pages provide their own inline Accept / Decline buttons.
const SUPPRESS_AUTO_POPUP_PATHS = ['/cookies', '/privacy']

// Pages where the banner should be a non-blocking bottom bar instead
// of a centred modal. Used on paid-traffic landing pages where the
// hero + lead-magnet form must be visible immediately and the cookie
// consent shouldn't get in the way of the conversion. Still GDPR
// compliant because both Accept and Decline are explicit buttons.
const BOTTOM_BAR_PATHS = ['/switch-to-centre-120', '/trinity-exam-information']

let pendingShow: ReturnType<typeof setTimeout> | null = null

function clearPending() {
  if (pendingShow) {
    clearTimeout(pendingShow)
    pendingShow = null
  }
}

function evaluateBanner(path: string) {
  const onSuppressedPage = SUPPRESS_AUTO_POPUP_PATHS.includes(path)
  const onBottomBarPage = BOTTOM_BAR_PATHS.includes(path)
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

  // Set the banner style for this page. Paid-traffic landing pages
  // use a non-blocking bottom bar; everything else uses the centred
  // modal (forces a choice for higher analytics opt-in on the main site).
  bannerStyle.value = onBottomBarPage ? 'bottom-bar' : 'modal'

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
  <!-- Modal style — blocks page interaction. Used on most pages to force -->
  <!-- a choice and maximise analytics opt-in. -->
  <Transition
    enter-from-class="opacity-0"
    enter-active-class="transition duration-500 ease-out"
    enter-to-class="opacity-100"
    leave-from-class="opacity-100"
    leave-active-class="transition duration-300 ease-in"
    leave-to-class="opacity-0"
  >
    <div
      v-if="isVisible && bannerStyle === 'modal'"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4"
    >
      <div class="w-full max-w-sm rounded-2xl bg-brand-surface p-6 shadow-2xl ring-1 ring-brand-border sm:p-8">
        <div class="flex flex-col items-center text-center">
          <div class="mb-4 rounded-full bg-brand-accent/10 p-3">
            <Cookie class="h-8 w-8 text-brand-accent" />
          </div>

          <h3 class="text-lg font-bold text-brand-text sm:text-xl">
            We use cookies
          </h3>
          <p class="mt-2 text-base leading-relaxed text-brand-text-soft sm:text-base">
            We use cookies to improve your experience and understand how our site is used.
          </p>

          <button
            @click="accept"
            class="mt-5 w-full cursor-pointer rounded-lg bg-brand-accent px-6 py-3 text-base font-semibold text-brand-text-inverse transition-colors hover:bg-brand-accent-dark sm:text-lg"
          >
            Accept all cookies
          </button>

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

  <!-- Bottom-bar style — non-blocking. Used on paid-traffic landing -->
  <!-- pages (BOTTOM_BAR_PATHS) where conversion matters more than -->
  <!-- forced analytics opt-in. Still GDPR compliant: both Accept and -->
  <!-- Decline are explicit buttons, plus Cookie Policy link. -->
  <Transition
    enter-from-class="translate-y-full opacity-0"
    enter-active-class="transition duration-500 ease-out"
    enter-to-class="translate-y-0 opacity-100"
    leave-from-class="translate-y-0 opacity-100"
    leave-active-class="transition duration-300 ease-in"
    leave-to-class="translate-y-full opacity-0"
  >
    <div
      v-if="isVisible && bannerStyle === 'bottom-bar'"
      class="fixed inset-x-0 bottom-0 z-50 border-t border-brand-border bg-brand-surface shadow-lg"
      role="region"
      aria-label="Cookie consent"
    >
      <div class="mx-auto flex max-w-6xl flex-col items-center gap-3 px-4 py-3 sm:flex-row sm:gap-4 sm:py-4">
        <Cookie class="h-5 w-5 shrink-0 text-brand-accent" aria-hidden="true" />
        <p class="flex-1 text-center text-sm text-brand-text sm:text-left">
          We use cookies to improve your experience.
          <a href="/cookies" class="text-brand-accent hover:underline">Learn more</a>
        </p>
        <div class="flex shrink-0 items-center gap-2">
          <button
            @click="decline"
            class="cursor-pointer rounded-md px-3 py-1.5 text-sm text-brand-text-soft transition-colors hover:bg-brand-bg hover:text-brand-text"
          >
            Necessary only
          </button>
          <button
            @click="accept"
            class="cursor-pointer rounded-md bg-brand-accent px-4 py-1.5 text-sm font-semibold text-brand-text-inverse transition-colors hover:bg-brand-accent-dark"
          >
            Accept all
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>
