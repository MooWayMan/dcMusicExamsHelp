<!-- resources/js/components/CookieConsent.vue -->
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Cookie } from 'lucide-vue-next'

const isVisible = ref(false)

onMounted(() => {
  // Only show if no preference has been set
  if (!localStorage.getItem('cookie-consent')) {
    // Small delay so it doesn't flash on page load
    setTimeout(() => {
      isVisible.value = true
    }, 1500)
  }
})

function accept() {
  localStorage.setItem('cookie-consent', 'accepted')
  isVisible.value = false

  // Load Google Analytics now that consent is given
  const s = document.createElement('script')
  s.async = true
  s.src = 'https://www.googletagmanager.com/gtag/js?id=G-TZJ8ZCZW3W'
  document.head.appendChild(s)
  window.dataLayer = window.dataLayer || []
  function gtag(...args: any[]) { window.dataLayer.push(args) }
  gtag('js', new Date())
  gtag('config', 'G-TZJ8ZCZW3W')
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
          <p class="mt-2 text-sm leading-relaxed text-brand-text-soft sm:text-base">
            We use cookies to improve your experience and understand how our site is used.
          </p>

          <!-- OK button — prominent -->
          <button
            @click="accept"
            class="mt-5 w-full cursor-pointer rounded-lg bg-brand-accent px-6 py-3 text-base font-semibold text-brand-text-inverse transition-colors hover:bg-brand-accent-dark sm:text-lg"
          >
            OK, got it
          </button>

          <!-- Cookie policy link — subtle -->
          <a
            href="/cookies"
            class="mt-3 text-sm text-brand-text-soft transition-colors hover:text-brand-accent hover:underline"
          >
            View Cookie Policy
          </a>
        </div>
      </div>
    </div>
  </Transition>
</template>
