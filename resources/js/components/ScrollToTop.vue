<!-- resources/js/components/ScrollToTop.vue -->
<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { ChevronUp } from 'lucide-vue-next'

const show = ref(false)

const handleScroll = () => {
  show.value = window.scrollY > 400
}

const scrollToTop = () => {
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

onMounted(() => window.addEventListener('scroll', handleScroll))
onUnmounted(() => window.removeEventListener('scroll', handleScroll))
</script>

<template>
  <Transition
    enter-active-class="transition duration-300 ease-out"
    enter-from-class="translate-y-4 opacity-0"
    enter-to-class="translate-y-0 opacity-100"
    leave-active-class="transition duration-200 ease-in"
    leave-from-class="translate-y-0 opacity-100"
    leave-to-class="translate-y-4 opacity-0"
  >
    <button
      v-if="show"
      @click="scrollToTop"
      class="fixed bottom-6 right-6 z-50 flex h-12 w-12 items-center justify-center rounded-full bg-brand-accent text-white shadow-lg transition hover:opacity-80 hover:shadow-xl sm:bottom-8 sm:right-8"
      aria-label="Back to top"
    >
      <ChevronUp class="h-6 w-6" />
    </button>
  </Transition>
</template>
