<!-- resources/js/components/examfees/FeePosterDownload.vue -->
<script setup lang="ts">
// The printable wall poster on /exam-fees: a small preview of the PNG and a
// button that opens the PDF. Teachers and music schools print it for the
// office wall. The two file addresses live with the year's fees in
// data/examFees.ts (poster), so this card only draws a year that has one.
import { Download } from 'lucide-vue-next'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import type { FeeSchedule } from '@/data/examFees'

const props = defineProps<{
  schedule: FeeSchedule
}>()
</script>

<template>
  <div
    v-if="props.schedule.poster"
    class="flex flex-col items-center gap-5 rounded-2xl border border-brand-border bg-brand-surface-soft p-5 sm:flex-row sm:items-center sm:gap-6"
  >
    <a
      :href="props.schedule.poster.pdf"
      target="_blank"
      rel="noopener"
      class="block w-36 flex-shrink-0 overflow-hidden rounded-lg border border-brand-border bg-white shadow-md transition-transform hover:-translate-y-0.5"
    >
      <img
        :src="props.schedule.poster.preview"
        :alt="`Preview of the ${props.schedule.year} Trinity exam fees poster for centre 120`"
        class="block h-auto w-full"
        loading="lazy"
      />
    </a>

    <div class="flex flex-1 flex-col items-center gap-3 sm:items-start">
      <MyTextConstructor variant="subheading" fontFamily="display" spacing="none" class="text-center sm:text-left">
        <template #myTitle>Print the {{ props.schedule.year }} fees poster</template>
      </MyTextConstructor>
      <div class="text-base">
        <MyTextConstructor bodyVariant="inherit" textColor="text-brand-text-soft" class="text-center sm:text-left">
          One A4 page with every grade's fee, centre code 120 and a QR code back to this page. Free to print for your studio or school office wall.
        </MyTextConstructor>
      </div>
      <a :href="props.schedule.poster.pdf" target="_blank" rel="noopener">
        <MyButtonConstructor variant="primary" size="medium" :icon="Download">
          Download the poster (PDF)
        </MyButtonConstructor>
      </a>
    </div>
  </div>
</template>
