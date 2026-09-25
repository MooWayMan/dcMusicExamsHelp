<!-- resources/js/pages/admin/Prizes/Index.vue -->
<script setup lang="ts">
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { ChevronDown, ChevronUp } from 'lucide-vue-next'
import PageHeader from '@/components/reusables/PageHeader.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MyTableConstructor from '@/components/reusables/MyTableConstructor.vue'
import PrizeWorkflowChecks, { type PrizeWorkflowStep } from '@/components/PrizeWorkflowChecks.vue'

interface PrizeRow {
  id: string
  quarter: number
  year: number
  quarter_label: string
  award_key: string
  prize: string
  winner: string
  amount_label: string
  steps: PrizeWorkflowStep[]
  status: Record<string, boolean>
  stage: string
  stage_label: string
  expires_label: string
}

const props = defineProps<{ prizes: PrizeRow[] }>()

// Grouped by what Paul has to do about them. The stage itself is decided in
// App\Services\PrizeLedger; this only chooses which table a stage sits in.
const SECTIONS = [
  {
    title: 'To do',
    subtitle: 'Winner emails still to send, and claimed prizes still to buy and send.',
    stages: ['email_not_sent', 'send_card'],
  },
  {
    title: 'Run out',
    subtitle: 'Their 12 months have passed. An unclaimed prize goes back into the prize fund; an unused card can be reclaimed.',
    stages: ['unclaimed', 'ran_out'],
  },
  {
    title: 'Waiting',
    subtitle: 'Told and not claimed yet, or card sent and not used yet. Check Your Orders on Amazon (Sent or Redeemed) and tick Used when it has been.',
    stages: ['waiting_for_claim', 'not_used'],
  },
]

const showUsed = ref(false)

const usedRows = computed(() => props.prizes.filter((p) => p.stage === 'done'))

const sections = computed(() => {
  const list = SECTIONS.map((s) => ({ ...s, rows: props.prizes.filter((p) => s.stages.includes(p.stage)) }))
  if (showUsed.value) {
    list.push({ title: 'Used', subtitle: 'Finished prizes.', stages: ['done'], rows: usedRows.value })
  }
  return list
})

const columns = [
  { key: 'quarter_label', title: 'Quarter' },
  { key: 'prize', title: 'Prize' },
  { key: 'winner', title: 'Winner' },
  { key: 'amount_label', title: 'Amount' },
  { key: 'stage_label', title: 'Where it is' },
  { key: 'expires_label', title: 'Runs out' },
  { key: 'progress', title: 'Progress' },
]

// A tick can move a prize to another table, so fetch the list again.
function refresh() {
  router.reload({ only: ['prizes'] })
}
</script>

<template>
  <div>
    <PageHeader
      title="Prizes"
      subtitle="Every gift token from every quarter: what still needs sending, what is waiting, and what has run out. Tick boxes here are the same ones as on Quarter End."
      eyebrow="Admin"
      size="compact"
    />

    <div class="mx-auto flex w-full max-w-6xl flex-col gap-8 px-4 py-8 sm:px-6 lg:px-8">
      <MyTableConstructor
        v-for="section in sections"
        :key="section.title"
        :title="`${section.title} (${section.rows.length})`"
        :subtitle="section.subtitle"
        :data="section.rows"
        :columns="columns"
        row-key="id"
        :sortable="false"
        :stack-on-mobile="true"
      >
        <template #cell-progress="{ row }">
          <PrizeWorkflowChecks
            :quarter="row.quarter"
            :year="row.year"
            :award-key="row.award_key"
            :winner-full-name="row.winner"
            :steps="row.steps"
            :initial="row.status"
            @saved="refresh"
          />
        </template>
      </MyTableConstructor>

      <div>
        <MyButtonConstructor size="small" variant="outline" :icon="showUsed ? ChevronUp : ChevronDown" @click="showUsed = ! showUsed">
          Used prizes ({{ usedRows.length }})
        </MyButtonConstructor>
      </div>
    </div>
  </div>
</template>
