<!-- resources/js/components/reusables/MySliderConstructor.vue -->
<script setup lang="ts">
import { computed } from 'vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'

defineOptions({ inheritAttrs: false })

// A slider with its value printed beside it. The value's box is sized to
// the widest value it can show (an invisible copy of it holds the width),
// so dragging from 5% to 100% never moves anything else in the row.
const props = withDefaults(defineProps<{
  min?: number
  max?: number
  step?: number
  suffix?: string
  ariaLabel?: string
  disabled?: boolean
}>(), {
  min: 0,
  max: 100,
  step: 1,
  suffix: '',
  ariaLabel: '',
  disabled: false,
})

const value = defineModel<number>({ required: true })

const widest = computed(() => `${props.max}${props.suffix}`)

function onInput(event: Event) {
  value.value = Number((event.target as HTMLInputElement).value)
}
</script>

<template>
  <div class="flex w-full items-center gap-3">
    <input
      type="range"
      :value="value"
      :min="min"
      :max="max"
      :step="step"
      :disabled="disabled"
      :aria-label="ariaLabel || undefined"
      :aria-valuetext="`${value}${suffix}`"
      class="h-2 min-w-0 flex-1 cursor-pointer accent-brand-accent disabled:cursor-not-allowed disabled:opacity-60"
      @input="onInput"
    >
    <div class="relative shrink-0 text-right text-sm">
      <span class="invisible" aria-hidden="true">{{ widest }}</span>
      <div class="absolute inset-0">
        <MyTextConstructor bodyVariant="inherit" alignment="right" spacing="none">{{ value }}{{ suffix }}</MyTextConstructor>
      </div>
    </div>
  </div>
</template>
