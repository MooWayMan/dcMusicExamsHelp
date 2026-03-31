import { ref, onMounted, nextTick } from 'vue'

/**
 * Composable for staggered page content animations.
 * Animations play on every Inertia page navigation because
 * Inertia unmounts/remounts page components on each visit.
 *
 * Usage:
 *   const { animClass } = usePageAnimation()
 *
 *   // In template:
 *   <div :class="animClass('fade-up', 0)">  // no delay
 *   <div :class="animClass('fade-up', 1)">  // 100ms delay
 *   <div :class="animClass('slide-left', 2)"> // 200ms delay
 */
export function usePageAnimation() {
  const ready = ref(false)

  onMounted(() => {
    // Start hidden, then after the browser paints the hidden state,
    // flip to visible so CSS transitions kick in
    ready.value = false
    nextTick(() => {
      requestAnimationFrame(() => {
        requestAnimationFrame(() => {
          ready.value = true
        })
      })
    })
  })

  const baseTransitions: Record<string, { initial: string; final: string }> = {
    'fade-up': {
      initial: 'translate-y-8 opacity-0',
      final: 'translate-y-0 opacity-100',
    },
    'fade-down': {
      initial: '-translate-y-8 opacity-0',
      final: 'translate-y-0 opacity-100',
    },
    'slide-left': {
      initial: 'translate-x-10 opacity-0',
      final: 'translate-x-0 opacity-100',
    },
    'slide-right': {
      initial: '-translate-x-10 opacity-0',
      final: 'translate-x-0 opacity-100',
    },
    'zoom-in': {
      initial: 'scale-90 opacity-0',
      final: 'scale-100 opacity-100',
    },
    'fade': {
      initial: 'opacity-0',
      final: 'opacity-100',
    },
  }

  const delayClasses = [
    '',           // 0 - no delay
    'delay-100',  // 1
    'delay-200',  // 2
    'delay-300',  // 3
    'delay-500',  // 4
    'delay-700',  // 5
  ]

  function animClass(type: string = 'fade-up', delayIndex: number = 0): string {
    const transition = baseTransitions[type] || baseTransitions['fade-up']
    const delay = delayClasses[Math.min(delayIndex, delayClasses.length - 1)] || ''
    const state = ready.value ? transition.final : transition.initial
    return `${state} transition-all duration-700 ease-out ${delay}`
  }

  return { ready, animClass }
}
