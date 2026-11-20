<!-- resources/js/components/examfees/MoreFeesSection.vue -->
<script setup lang="ts">
// Certificate exams and the Musical Development awards, folded away in an
// accordion under the main fee tables. Only drawn for a year that has them.
import { computed } from 'vue'
import { usePageAnimation } from '@/composables/usePageAnimation'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyTableConstructor from '@/components/reusables/MyTableConstructor.vue'
import MyAccordionConstructor from '@/components/reusables/MyAccordionConstructor.vue'
import { certificateRows, gradeRows, formatFee, type MoreFees } from '@/data/examFees'

const props = defineProps<{
  more: MoreFees
  year: number
}>()

const { animClass } = usePageAnimation()

const items = [
  { id: 'certificates', question: 'Certificate exams: solo, pairs, groups and choirs' },
  { id: 'musical-development', question: 'Awards and Certificates in Musical Development' },
]

const certificateColumns = [
  { key: 'level', title: 'Certificate', sortable: false },
  { key: 'solo', title: 'Solo / group', sortable: false, align: 'right' as const },
  { key: 'pairs', title: 'Pairs', sortable: false, align: 'right' as const },
  { key: 'choral', title: 'Choral', sortable: false, align: 'right' as const },
]

const levelColumns = [
  { key: 'grade', title: 'Level', sortable: false },
  { key: 'fee', title: 'Fee (GBP)', sortable: false, align: 'right' as const },
]

const certificateData = computed(() => certificateRows(props.more))
const musicalDevelopmentData = computed(() => gradeRows(props.more.musicalDevelopment))
</script>

<template>
  <section id="more-fees" class="bg-brand-surface-soft">
    <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:py-16">
      <div :class="animClass('fade-up', 1)">
        <MyTextConstructor
          variant="subheading"
          fontFamily="display"
          alignment="center"
          spacing="tight"
          class="md:!text-2xl lg:!text-3xl"
        >
          <template #myTitle>More fees {{ props.year }}</template>
        </MyTextConstructor>
        <div class="mx-auto mt-3 max-w-2xl text-base md:text-lg">
          <MyTextConstructor bodyVariant="inherit" alignment="center" textColor="text-brand-text-soft">
            Certificate exams for soloists, pairs, groups and choirs, and the Awards and Certificates in Musical Development.
          </MyTextConstructor>
        </div>
      </div>

      <div :class="animClass('fade-up', 2)" class="mt-8">
        <MyAccordionConstructor
          :items="items"
          size="small"
          header-bg-color="bg-gradient-to-r from-brand-primary via-brand-accent to-brand-primary"
          header-text-color="text-brand-text-inverse"
          header-hover-bg-color="hover:opacity-90"
          border-color="border-brand-primary"
          content-bg-color="bg-brand-surface"
        >
          <template #content-certificates>
            <MyTableConstructor
              :data="certificateData"
              :columns="certificateColumns"
              rowKey="level"
              :sortable="false"
              :striped="true"
              :bordered="true"
              size="medium"
            />
            <div class="mt-3 text-sm md:text-base">
              <MyTextConstructor bodyVariant="inherit" textColor="text-brand-text-soft">
                A group certificate exam costs the solo fee plus {{ formatFee(props.more.groupMemberFee) }} for each group member.
              </MyTextConstructor>
            </div>
          </template>
          <template #content-musical-development>
            <MyTableConstructor
              :data="musicalDevelopmentData"
              :columns="levelColumns"
              rowKey="grade"
              :sortable="false"
              :striped="true"
              :bordered="true"
              size="medium"
            />
            <div class="mt-3 text-sm md:text-base">
              <MyTextConstructor bodyVariant="inherit" textColor="text-brand-text-soft">
                These are taken through validated centres. Trinity's website explains how a centre becomes one.
              </MyTextConstructor>
            </div>
          </template>
        </MyAccordionConstructor>
      </div>
    </div>
  </section>
</template>
