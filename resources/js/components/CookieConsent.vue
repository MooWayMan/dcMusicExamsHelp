<!-- resources/js/components/CookieConsent.vue -->
<script setup lang="ts">
import { ref, onMounted } from 'vue'

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
    enter-from-class="translate-y-full opacity-0"
    enter-active-class="transition duration-500 ease-out"
    enter-to-class="translate-y-0 opacity-100"
    leave-from-class="translate-y-0 opacity-100"
    leave-active-class="transition duration-300 ease-in"
    leave-to-class="translate-y-full opacity-0"
  >
    <div
      v-if="isVisible"
      class="fixed bottom-0 left-0 right-0 z-50 border-t border-brand-border bg-brand-surface shadow-2xl"
    >
      <div class="mx-auto flex max-w-5xl flex-col items-center gap-3 px-4 py-4 sm:flex-row sm:px-6 sm:py-4">
        <p class="flex-1 text-sm leading-relaxed text-brand-text sm:text-base">
          We use cookies to improve your experience.
          <a href="/cookies" class="font-medium text-brand-accent hover:underline">Cookie Policy</a>
        </p>
        <button
          @click="accept"
          class="cursor-pointer rounded-full bg-brand-accent px-6 py-2 text-sm font-semibold text-brand-text-inverse transition-colors hover:bg-brand-accent-dark sm:text-base"
        >
          OK
        </button>
      </div>
    </div>
  </Transition>
</template>
