<!-- resources/js/components/examfees/FeeYearSwitch.vue -->
<script setup lang="ts">
// "2026 fees | 2027 fees". Only drawn while two years are published; the
// page drops the old year itself the day after it ends.
import { computed } from 'vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import { formatDay, type FeeSchedule } from '@/data/examFees'

const props = defineProps<{
  years: FeeSchedule[]
  currentYear: number
}>()

const year = defineModel<number>({ required: true })

const shown = computed(() => props.years.find((s) => s.year === year.value) ?? props.years[0])
const newest = computed(() => props.years[props.years.length - 1])
</script>

<template>
  <section v-if="props.years.length > 1" id="fee-years" class="scroll-mt-20 bg-brand-surface">
    <div class="mx-auto max-w-4xl px-4 py-6 sm:px-6">
      <div class="flex items-center justify-center gap-3">
        <MyButtonConstructor
          v-for="option in props.years"
          :key="option.year"
          :variant="option.year === year ? 'secondary' : 'outline'"
          size="medium"
          @click="year = option.year"
        >
          {{ option.year }} fees
        </MyButtonConstructor>
      </div>
      <div class="mt-3 text-sm md:text-base">
        <MyTextConstructor bodyVariant="inherit" alignment="center" textColor="text-brand-text-soft">
          <template v-if="shown.year > props.currentYear">These fees apply from {{ formatDay(shown.from) }}.</template>
          <template v-else>These fees apply until {{ formatDay(shown.to) }}. Trinity's new fees start on {{ formatDay(newest.from) }}.</template>
        </MyTextConstructor>
      </div>
    </div>
  </section>
</template>
