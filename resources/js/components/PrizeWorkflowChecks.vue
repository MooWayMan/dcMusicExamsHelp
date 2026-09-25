<!-- resources/js/components/PrizeWorkflowChecks.vue -->
<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { CheckCircle2 } from 'lucide-vue-next'
import { xsrfToken } from '@/lib/utils'

export interface PrizeWorkflowStep {
  key: string
  label: string
}

const props = defineProps<{
  quarter: number
  year: number
  awardKey: string
  winnerFullName: string
  steps: PrizeWorkflowStep[]
  initial?: Record<string, boolean>
}>()

const status = ref<Record<string, boolean>>({ ...(props.initial ?? {}) })

watch(() => props.initial, (next) => {
  status.value = { ...(next ?? {}) }
})

const allDone = computed(() => props.steps.length > 0 && props.steps.every((s) => status.value[s.key]))

async function toggle(step: string) {
  const previous = { ...status.value }
  const value = ! previous[step]
  status.value = { ...previous, [step]: value }

  try {
    const res = await fetch('/admin/quarter-end/toggle-workflow', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-XSRF-TOKEN': xsrfToken(),
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        quarter: props.quarter,
        year: props.year,
        award_key: props.awardKey,
        winner_full_name: props.winnerFullName,
        step,
        value,
      }),
    })
    if (! res.ok) throw new Error('Toggle failed')
    const data = await res.json()
    if (data?.status) status.value = data.status
  } catch (e) {
    status.value = previous
    console.error('Failed to save workflow step', e)
  }
}
</script>

<template>
  <div class="basis-full mt-1 flex flex-wrap items-center gap-3 text-xs text-brand-text">
    <span class="font-semibold">Progress:</span>
    <label v-for="step in steps" :key="step.key" class="inline-flex cursor-pointer items-center gap-1.5">
      <input
        type="checkbox"
        :checked="!!status[step.key]"
        class="rounded border-brand-border accent-brand-accent"
        @change="toggle(step.key)"
      >
      <span>{{ step.label }}</span>
    </label>
    <span v-if="allDone" class="ml-auto inline-flex items-center gap-1 rounded-full bg-brand-success/20 px-2 py-0.5 font-semibold text-brand-success">
      <CheckCircle2 class="h-3 w-3" /> All done
    </span>
  </div>
</template>
