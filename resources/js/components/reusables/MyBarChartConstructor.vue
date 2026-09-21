<!-- resources/js/components/reusables/MyBarChartConstructor.vue -->
<!--
  MyBarChartConstructor: one series of counts as columns, e.g. page opens
  per day. Built 21 Sep 2026 for Site stats and meant for every site's copy
  of that page.

  One series, one hue (--chart-count), one axis. Two measures on different
  scales get two charts side by side, never a second y-axis.

  Only the tallest bar carries its number; every bar shows its value on
  hover, and the page puts the same numbers in a table, so nothing is
  hover-only. Bars are rounded at the data end and square at the baseline.

  Usage:
  <MyBarChartConstructor
    title="Page opens"
    :bars="[{ label: '21', full: 'Mon 21 Sep', value: 12 }]"
    noun="page open"
  />
-->
<script setup lang="ts">
import { computed, ref } from 'vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import { topRounded } from '@/lib/chartShapes'
import { useChartTip } from '@/composables/useChartTip'
import MyChartTooltip from '@/components/reusables/MyChartTooltip.vue'

export interface BarChartBar {
  /** Short label under the bar ("21", "Sep"). */
  label: string
  /** Full name for the tooltip ("Mon 21 Sep"). */
  full: string
  value: number
}

interface Props {
  title: string
  bars: BarChartBar[]
  /** What one unit is, for the tooltip: "12 page opens". */
  noun?: string
  nounPlural?: string
  /** Shown instead of the plot when every bar is zero. */
  emptyText?: string
}

const props = withDefaults(defineProps<Props>(), {
  noun: 'item',
  nounPlural: '',
  emptyText: 'Nothing counted in this period yet.',
})

const PLOT_H = 120
const TOP_PAD = 16
const LABEL_H = 20
const SLOT = 24
const BAR_W = 16
const MAX_LABELS = 14

const maxValue = computed(() => Math.max(0, ...props.bars.map((b) => b.value)))
const isEmpty = computed(() => maxValue.value === 0)
const width = computed(() => Math.max(1, props.bars.length) * SLOT)
const height = PLOT_H + TOP_PAD + LABEL_H
const labelStep = computed(() => Math.max(1, Math.ceil(props.bars.length / MAX_LABELS)))
const tallestIndex = computed(() => props.bars.findIndex((b) => b.value === maxValue.value))

const columns = computed(() =>
  props.bars.map((b, i) => {
    const h = b.value === 0 ? 0 : Math.max(2, (b.value / maxValue.value) * PLOT_H)
    return {
      ...b,
      i,
      x: i * SLOT + (SLOT - BAR_W) / 2,
      y: TOP_PAD + PLOT_H - h,
      h,
    }
  }),
)

function unitWord(n: number): string {
  if (n === 1) return props.noun
  return props.nounPlural || `${props.noun}s`
}

const root = ref<HTMLElement | null>(null)
const { tip, showTip, hideTip } = useChartTip()

function showBarTip(event: MouseEvent, bar: BarChartBar) {
  showTip(event, bar.full, `${bar.value.toLocaleString('en-GB')} ${unitWord(bar.value)}`, root.value)
}
</script>

<template>
  <div ref="root" class="relative rounded-xl border border-brand-border bg-brand-surface p-5">
    <MyTextConstructor spacing="none">
      <template #mySubTitle>{{ title }}</template>
    </MyTextConstructor>

    <div v-if="isEmpty" class="pt-4">
      <MyTextConstructor spacing="none">
        <template #mySubTitle>{{ emptyText }}</template>
      </MyTextConstructor>
    </div>

    <div v-else class="overflow-x-auto pt-4">
      <svg
        :viewBox="`0 0 ${width} ${height}`"
        :width="width"
        :height="height"
        class="max-w-full"
        role="img"
        :aria-label="title"
      >
        <line
          x1="0"
          :y1="TOP_PAD + PLOT_H"
          :x2="width"
          :y2="TOP_PAD + PLOT_H"
          class="stroke-brand-border"
          stroke-width="1"
        />

        <template v-for="c in columns" :key="`${c.i}-${c.full}`">
          <path
            v-if="c.h > 0"
            :d="topRounded(c.x, c.y, BAR_W, c.h)"
            fill="var(--chart-count)"
          />
          <text
            v-if="c.i === tallestIndex"
            :x="c.x + BAR_W / 2"
            :y="c.y - 5"
            text-anchor="middle"
            class="fill-brand-text text-[10px] font-semibold tabular-nums"
          >{{ c.value.toLocaleString('en-GB') }}</text>
          <text
            v-if="c.i % labelStep === 0"
            :x="c.i * SLOT + SLOT / 2"
            :y="TOP_PAD + PLOT_H + 14"
            text-anchor="middle"
            class="fill-brand-text-soft text-[10px]"
          >{{ c.label }}</text>
          <rect
            :x="c.i * SLOT"
            y="0"
            :width="SLOT"
            :height="TOP_PAD + PLOT_H"
            fill="transparent"
            @mousemove="showBarTip($event, c)"
            @mouseleave="hideTip"
          />
        </template>
      </svg>
    </div>

    <MyChartTooltip :tip="tip" />
  </div>
</template>
