<!-- resources/js/components/reusables/MyChartTooltip.vue -->
<!--
  The hover label for SVG charts. Pair with useChartTip; place it inside the
  chart's positioned (relative) container.
-->
<script setup lang="ts">
import type { ChartTip } from '@/composables/useChartTip'

defineProps<{ tip: ChartTip | null }>()
</script>

<template>
  <div
    v-if="tip"
    class="pointer-events-none absolute z-20 -translate-x-1/2 -translate-y-full rounded-lg border border-brand-border bg-brand-surface px-2.5 py-1.5 text-xs shadow-lg"
    :style="{ left: `${tip.x}px`, top: `${tip.y - 8}px` }"
  >
    <span class="font-semibold text-brand-text">{{ tip.label }}</span>
    <span class="ml-2 text-brand-text-soft">{{ tip.value }}</span>
  </div>
</template>
