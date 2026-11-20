<!-- resources/js/components/examfees/ExamFeesTab.vue -->
<script setup lang="ts">
// The Exam Fees tab on /exam-fees: year switch, the four fee tables, More
// fees, the small print and the jump to the dates tab. Every figure comes
// from data/examFees.ts through the schedule it is handed.
import { computed } from 'vue'
import { Calendar } from 'lucide-vue-next'
import { usePageAnimation } from '@/composables/usePageAnimation'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyTableConstructor from '@/components/reusables/MyTableConstructor.vue'
import MyPhotoSection from '@/components/reusables/MyPhotoSection.vue'
import FeeYearSwitch from '@/components/examfees/FeeYearSwitch.vue'
import MoreFeesSection from '@/components/examfees/MoreFeesSection.vue'
import TabCrossRef from '@/components/examfees/TabCrossRef.vue'
import { gradeRows, diplomaRows, type FeeSchedule } from '@/data/examFees'

const props = defineProps<{
  fees: FeeSchedule
  years: FeeSchedule[]
  currentYear: number
  crossRefVisible: boolean
}>()

const year = defineModel<number>('year', { required: true })
const emit = defineEmits<{ showDates: [] }>()

const { animClass } = usePageAnimation()

const gradeColumns = [
  { key: 'grade', title: 'Grade', sortable: false },
  { key: 'fee', title: 'Fee (GBP)', sortable: false, align: 'right' as const },
]

const diplomaF2fColumns = [
  { key: 'diploma', title: 'Diploma', sortable: false },
  { key: 'unit', title: 'Unit', sortable: false },
  { key: 'fee', title: 'Fee (GBP)', sortable: false, align: 'right' as const },
]

const diplomaDigitalColumns = [
  { key: 'diploma', title: 'Diploma', sortable: false },
  { key: 'fee', title: 'Fee (GBP)', sortable: false, align: 'right' as const },
]

const f2fData = computed(() => gradeRows(props.fees.faceToFace))
const digitalData = computed(() => gradeRows(props.fees.digital))
const theoryData = computed(() => gradeRows(props.fees.theory))
const diplomaF2fData = computed(() => diplomaRows(props.fees.diplomasFaceToFace))
const diplomaDigitalData = computed(() => diplomaRows(props.fees.diplomasDigital))

const gradeTables = computed(() => [
  {
    key: 'f2f',
    image: 'blue_BG_5.jpg',
    title: `Face-to-face exam fees ${props.fees.year}`,
    subtitle: 'Classical & Jazz and Rock & Pop — same fees for both. Exams take place at our centres in Liverpool and Wirral.',
    rows: f2fData.value,
  },
  {
    key: 'digital',
    image: 'blue_BG_8.jpg',
    title: `Digital exam fees ${props.fees.year}`,
    subtitle: 'Classical & Jazz and Rock & Pop — same fees for both. Record anywhere and submit online.',
    rows: digitalData.value,
  },
  {
    key: 'theory',
    image: 'blue_BG_10.jpg',
    title: `Theory exam fees ${props.fees.year}`,
    subtitle: 'Digital only. No Initial grade for theory — starts at Grade 1. Trinity does not require theory for practical exams at any grade.',
    rows: theoryData.value,
  },
])

const REGULATIONS_URL = 'https://www.trinitycollege.com/qualifications/music/resources/general-resources/our-music-exams/information-regulations-from-2017'
</script>

<template>
  <div>
    <FeeYearSwitch v-model="year" :years="props.years" :current-year="props.currentYear" />

    <MyPhotoSection
      v-for="table in gradeTables"
      :key="table.key"
      :image="table.image"
      :title="table.title"
      :subtitle="table.subtitle"
    >
      <div :class="animClass('fade-up', 2)" class="mt-8">
        <MyTableConstructor
          :data="table.rows"
          :columns="gradeColumns"
          rowKey="grade"
          :sortable="false"
          :striped="true"
          :bordered="true"
          size="large"
        />
      </div>
    </MyPhotoSection>

    <MyPhotoSection
      image="blue_BG_6.jpg"
      :title="`Diploma fees ${props.fees.year}`"
      subtitle="For candidates beyond Grade 8. Associate (ATCL), Licentiate (LTCL) and Fellowship (FTCL) diplomas."
    >
      <div :class="animClass('fade-up', 2)" class="mt-8">
        <div class="mb-3 text-lg font-semibold">
          <MyTextConstructor bodyVariant="inherit" textColor="text-white">Face-to-face diplomas</MyTextConstructor>
        </div>
        <MyTableConstructor
          :data="diplomaF2fData"
          :columns="diplomaF2fColumns"
          rowKey="key"
          :sortable="false"
          :striped="true"
          :bordered="true"
          size="large"
        />
      </div>

      <div :class="animClass('fade-up', 3)" class="mt-10">
        <div class="mb-3 text-lg font-semibold">
          <MyTextConstructor bodyVariant="inherit" textColor="text-white">
            Digital diplomas<template v-if="props.fees.diplomasDigitalTag"> ({{ props.fees.diplomasDigitalTag }})</template>
          </MyTextConstructor>
        </div>
        <MyTableConstructor
          :data="diplomaDigitalData"
          :columns="diplomaDigitalColumns"
          rowKey="key"
          :sortable="false"
          :striped="true"
          :bordered="true"
          size="large"
        />
      </div>
    </MyPhotoSection>

    <MoreFeesSection v-if="props.fees.more" :more="props.fees.more" :year="props.fees.year" />

    <section class="bg-black">
      <div class="mx-auto max-w-4xl px-4 py-6 text-sm sm:px-6">
        <MyTextConstructor bodyVariant="inherit" alignment="center" textColor="text-white/60">
          All fees are set by Trinity College London and are subject to change. Late entry surcharges may apply.
          See <a :href="REGULATIONS_URL" target="_blank" rel="noopener noreferrer" class="font-semibold text-brand-accent underline hover:opacity-70">Trinity's policies and regulations</a> for full terms and conditions.
        </MyTextConstructor>
      </div>
    </section>

    <TabCrossRef
      title="Looking for exam dates?"
      button-label="View all exam dates"
      :icon="Calendar"
      :visible="props.crossRefVisible"
      @go="emit('showDates')"
    >
      Three face-to-face sessions per year: <span class="font-medium text-brand-text">March</span>,
      <span class="font-medium text-brand-text">July</span> and
      <span class="font-medium text-brand-text">November</span>.
      Digital exams can be booked any time.
    </TabCrossRef>
  </div>
</template>
