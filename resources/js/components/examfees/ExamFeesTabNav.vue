<!-- resources/js/components/examfees/ExamFeesTabNav.vue -->
<script setup lang="ts">
// The sticky Exam Fees / Exam Dates pills at the top of /exam-fees.
import { PoundSterling, Calendar } from 'lucide-vue-next'

export type ExamFeesTab = 'fees' | 'dates'

const tab = defineModel<ExamFeesTab>({ required: true })

const tabs = [
  { key: 'fees' as const, label: 'Exam Fees', icon: PoundSterling },
  { key: 'dates' as const, label: 'Exam Dates', icon: Calendar },
]
</script>

<template>
  <section class="sticky top-0 z-30 border-b border-brand-border bg-brand-surface shadow-sm">
    <div class="mx-auto flex max-w-4xl items-center justify-center gap-2 px-4 py-3 sm:px-6">
      <button
        v-for="item in tabs"
        :key="item.key"
        type="button"
        @click="tab = item.key"
        :class="[
          'flex items-center gap-2 rounded-full px-6 py-2.5 text-sm font-semibold transition-all duration-200 sm:text-base',
          tab === item.key
            ? 'bg-gradient-to-r from-brand-primary to-brand-accent text-white shadow-md'
            : 'bg-brand-surface-soft text-brand-text-soft hover:text-brand-text hover:bg-brand-border'
        ]"
      >
        <component :is="item.icon" class="h-4 w-4" />
        {{ item.label }}
      </button>
    </div>
  </section>
</template>
