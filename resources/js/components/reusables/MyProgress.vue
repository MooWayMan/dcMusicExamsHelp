<!-- resources/js/components/reusables/MyProgress.vue -->
<script setup lang="ts">
import { computed } from 'vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'

// No amber: Paul's palette has none, and a warning is shown in blue.
interface Props {
  percentage?: number
  label?: string
  color?: 'blue' | 'green' | 'red' | 'purple'
  animated?: boolean
  striped?: boolean
  indeterminate?: boolean
  /** Small text and a thin bar, for a table cell or a card row. */
  compact?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  percentage: 0,
  label: '',
  color: 'blue',
  animated: false,
  striped: false,
  indeterminate: false,
  compact: false,
})

const colorMap = {
  blue: 'bg-brand-accent',
  green: 'bg-brand-success',
  red: 'bg-brand-danger',
  purple: 'bg-brand-primary',
}

const barHeight = computed(() => (props.compact ? 'h-2' : 'h-3'))

const barClasses = computed(() => [
  barHeight.value,
  'rounded-full transition-all duration-500',
  colorMap[props.color],
  props.striped ? 'bg-[length:20px_20px] bg-stripes' : '',
  props.animated ? 'animate-pulse' : '',
])
</script>

<template>
  <div :class="compact ? 'space-y-1 text-sm' : 'space-y-2'">
    <div class="flex justify-between gap-2">
      <MyTextConstructor :bodyVariant="compact ? 'inherit' : 'body'" spacing="none">
        {{ label }}
      </MyTextConstructor>

      <MyTextConstructor :bodyVariant="compact ? 'inherit' : 'body'" textColor="text-brand-text-soft" spacing="none">
        {{ percentage }}%
      </MyTextConstructor>
    </div>

    <div class="w-full overflow-hidden rounded-full bg-brand-border" :class="barHeight">
      <div
        v-if="!indeterminate"
        :class="barClasses"
        :style="{ width: percentage + '%' }"
      />
      <div
        v-else
        class="w-1/2 animate-pulse rounded-full bg-brand-accent"
        :class="barHeight"
      />
    </div>
  </div>
</template>
