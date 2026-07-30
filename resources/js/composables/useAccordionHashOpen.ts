// resources/js/composables/useAccordionHashOpen.ts
import { onMounted, nextTick } from 'vue'

export function useAccordionHashOpen() {
  onMounted(() => {
    const match = window.location.hash.match(/^#faq-(\d+)$/)
    if (!match) return
    nextTick(() => {
      window.setTimeout(() => {
        const btn = document.getElementById('accordion-btn-' + match[1])
        if (!btn) return
        if (btn.getAttribute('aria-expanded') !== 'true') btn.click()
        const top = btn.getBoundingClientRect().top + window.scrollY - 100
        window.scrollTo({ top, behavior: 'smooth' })
      }, 150)
    })
  })
}
