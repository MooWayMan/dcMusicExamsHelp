<!-- resources/js/components/NewsletterPopup.vue -->
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { X, Mail } from 'lucide-vue-next'
import { useSubscription } from '@/composables/useSubscription'
import EmailCapture from '@/components/EmailCapture.vue'

const { isSubscribed } = useSubscription()
const isVisible = ref(false)

onMounted(() => {
  // Don't show if already subscribed or already dismissed this session
  if (isSubscribed.value) return
  if (sessionStorage.getItem('newsletter-popup-dismissed')) return

  // Show after 5 seconds
  setTimeout(() => {
    // Re-check in case they subscribed via an inline form in the meantime
    if (!isSubscribed.value) {
      isVisible.value = true
    }
  }, 5000)
})

function dismiss() {
  isVisible.value = false
  sessionStorage.setItem('newsletter-popup-dismissed', 'true')
}
</script>

<template>
  <Transition
    enter-from-class="opacity-0"
    enter-active-class="transition duration-300 ease-out"
    enter-to-class="opacity-100"
    leave-from-class="opacity-100"
    leave-active-class="transition duration-200 ease-in"
    leave-to-class="opacity-0"
  >
    <div
      v-if="isVisible && !isSubscribed"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
      @click.self="dismiss"
    >
      <Transition
        enter-from-class="scale-95 opacity-0"
        enter-active-class="transition duration-300 ease-out"
        enter-to-class="scale-100 opacity-100"
        appear
      >
        <div class="relative w-full max-w-md rounded-2xl bg-brand-surface p-6 shadow-2xl sm:p-8">
          <!-- Close button -->
          <button
            @click="dismiss"
            class="absolute right-3 top-3 cursor-pointer rounded-full p-1 text-brand-text-soft transition-colors hover:bg-brand-surface-soft hover:text-brand-text"
          >
            <X class="h-5 w-5" />
          </button>

          <!-- Header -->
          <div class="mb-5 text-center">
            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-brand-accent/10">
              <Mail class="h-6 w-6 text-brand-accent" />
            </div>
            <h3 class="text-lg font-bold text-brand-text sm:text-xl">
              Stay in the loop
            </h3>
            <p class="mt-1 text-sm text-brand-text-soft sm:text-base">
              Tips, exam dates, book discounts and more — straight to your inbox.
            </p>
          </div>

          <!-- Email capture form -->
          <EmailCapture source="popup" variant="light" />

          <!-- No thanks -->
          <button
            @click="dismiss"
            class="mt-3 w-full cursor-pointer text-center text-xs text-brand-text-soft transition-colors hover:text-brand-text sm:text-sm"
          >
            No thanks, maybe later
          </button>
        </div>
      </Transition>
    </div>
  </Transition>
</template>
