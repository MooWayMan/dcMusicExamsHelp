// resources/js/composables/useBookingModal.ts
import { ref, onMounted, onUnmounted } from 'vue'

export function useBookingModal() {
  const showBookingModal = ref(false)

  const openModal = () => {
    showBookingModal.value = true
  }

  onMounted(() => {
    window.addEventListener('open-booking-modal', openModal)
  })

  onUnmounted(() => {
    window.removeEventListener('open-booking-modal', openModal)
  })

  return { showBookingModal }
}
