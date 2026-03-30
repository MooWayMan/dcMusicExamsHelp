<!-- resources/js/components/reusables/MyProgress.vue -->
<script setup lang="ts">
import { computed } from 'vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'

interface Props {
  percentage?: number
  label?: string
  color?: 'blue' | 'green' | 'amber' | 'red' | 'purple'
  animated?: boolean
  striped?: boolean
  indeterminate?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  percentage: 0,
  label: '',
  color: 'blue',
  animated: false,
  striped: false,
  indeterminate: false,
})

const colorMap = {
  blue: 'bg-brand-accent',
  green: 'bg-brand-success',
  amber: 'bg-yellow-500',
  red: 'bg-brand-danger',
  purple: 'bg-purple-500',
}

const barClasses = computed(() => [
  'h-3 rounded-full transition-all duration-500',
  colorMap[props.color],
  props.striped ? 'bg-[length:20px_20px] bg-stripes' : '',
  props.animated ? 'animate-pulse' : '',
])
</script>

<template>
  <div class="space-y-2">
    <div class="flex justify-between">
      <MyTextConstructor variant="body">
        {{ label }}
      </MyTextConstructor>

      <MyTextConstructor variant="body" textColor="soft">
        {{ percentage }}%
      </MyTextConstructor>
    </div>

    <div class="h-3 w-full rounded-full bg-brand-border overflow-hidden">
      <div
        v-if="!indeterminate"
        :class="barClasses"
        :style="{ width: percentage + '%' }"
      />
      <div
        v-else
        class="h-3 w-1/2 animate-pulse rounded-full bg-brand-accent"
      />
    </div>
  </div>
</template>