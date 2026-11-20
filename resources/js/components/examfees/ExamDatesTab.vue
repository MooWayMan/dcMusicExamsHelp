<!-- resources/js/components/examfees/ExamDatesTab.vue -->
<script setup lang="ts">
// The Exam Dates tab on /exam-fees: next exam days per venue, Trinity's
// booking windows, and the jump back to the fees tab.
import { PoundSterling } from 'lucide-vue-next'
import { usePageAnimation } from '@/composables/usePageAnimation'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyTableConstructor from '@/components/reusables/MyTableConstructor.vue'
import MyPhotoSection from '@/components/reusables/MyPhotoSection.vue'
import TabCrossRef from '@/components/examfees/TabCrossRef.vue'
import { currentSchedule, lowestFee } from '@/data/examFees'

const props = defineProps<{ crossRefVisible: boolean }>()
const emit = defineEmits<{ showFees: [] }>()

const { animClass } = usePageAnimation()

/* ── "From £..." line: this year's fees ── */
const current = currentSchedule(new Date())
const fromDigital = lowestFee(current.digital)
const fromFaceToFace = lowestFee(current.faceToFace)
const fromTheory = lowestFee(current.theory)

/* ── Exam dates ──
   One table per VENUE (Paul, 28 Oct 2026: make the two venues clear), one row
   per exam day, because each venue and exam type is its own Trinity session
   with its own closing date. "Book by" is Paul's own date, deliberately ahead
   of Trinity's. "Trinity closes" is what Trinity's booking systems showed,
   checked 28 Oct 2026. Kept to four short columns so the table fits a phone
   without stacking into cards. Past sessions come off; add the next session's
   rows when Trinity opens it. */
const dateColumns = [
  { key: 'exam', title: 'Exam', sortable: false },
  { key: 'day', title: 'Day', sortable: false },
  { key: 'bookBy', title: 'Book By', sortable: false },
  { key: 'closes', title: 'Trinity Closes', sortable: false },
]

const examVenues = [
  {
    name: 'Liverpool — Learn Music',
    area: 'Stoneycroft, L13',
    days: [
      { exam: 'Classical & Jazz', day: 'Thu 3 Dec 2026', bookBy: 'Fri 30 Oct', closes: 'Thu 5 Nov' },
      { exam: 'Rock & Pop', day: 'Sat 5 Dec 2026', bookBy: 'Fri 30 Oct', closes: 'Sat 7 Nov' },
    ],
  },
  {
    name: 'Wirral — Wirral School of Music / Tafelmusik',
    area: 'CH43',
    days: [
      { exam: 'Classical & Jazz', day: 'Fri 4 Dec 2026', bookBy: 'Fri 30 Oct', closes: 'Fri 6 Nov' },
    ],
  },
]

const bookingWindowColumns = [
  { key: 'session', title: 'Session Period', sortable: false },
  { key: 'opens', title: 'Booking Opens', sortable: false },
]

const bookingWindowData = [
  { session: 'March 2026 – May 2026', opens: 'Wed 14 January 2026 at 2pm' },
  { session: 'June 2026 – October 2026', opens: 'Wed 15 April 2026 at 2pm' },
  { session: 'November 2026 – February 2027', opens: 'Wed 2 September 2026 at 2pm' },
  { session: 'March 2027 – May 2027', opens: 'Wed 13 January 2027 at 2pm' },
]
</script>

<template>
  <div>
    <MyPhotoSection
      id="dates"
      image="blue_BG_7.jpg"
      title="Face-to-face exam dates 2026"
      subtitle="Liverpool and Wirral — three sessions per year for both Classical & Jazz and Rock & Pop. Digital exams can be booked and submitted at any time."
      rule
    >
      <div :class="animClass('fade-up', 2)" class="mt-8">
        <div class="mb-3 text-lg font-semibold">
          <MyTextConstructor bodyVariant="inherit" textColor="text-white">Next exam days and closing dates</MyTextConstructor>
        </div>
        <div v-for="venue in examVenues" :key="venue.name" class="mt-6">
          <MyTextConstructor variant="button-lg" spacing="none" textColor="text-white">
            <template #myTitle>{{ venue.name }}</template>
          </MyTextConstructor>
          <div class="mt-1 text-sm md:text-base">
            <MyTextConstructor bodyVariant="inherit" textColor="text-white/60">{{ venue.area }}</MyTextConstructor>
          </div>
          <MyTableConstructor
            :data="venue.days"
            :columns="dateColumns"
            rowKey="day"
            :sortable="false"
            :striped="true"
            :bordered="true"
            size="medium"
          />
        </div>
        <div class="text-sm md:text-base">
          <MyTextConstructor bodyVariant="inherit" textColor="text-white/80">
            Book by is our recommended date. Trinity's own booking closes a few days later, or sooner if the session fills.
          </MyTextConstructor>
        </div>
      </div>

      <div :class="animClass('fade-up', 3)" class="mt-10">
        <div class="mb-3 text-lg font-semibold">
          <MyTextConstructor bodyVariant="inherit" textColor="text-white">Online booking windows</MyTextConstructor>
        </div>
        <MyTableConstructor
          :data="bookingWindowData"
          :columns="bookingWindowColumns"
          rowKey="session"
          :sortable="false"
          :striped="true"
          :bordered="true"
          size="large"
        />
        <div class="mt-3 text-sm">
          <MyTextConstructor bodyVariant="inherit" textColor="text-white/60">
            These booking windows are for face-to-face Classical &amp; Jazz exams. Rock &amp; Pop face-to-face exams are booked on MyTrinity, where each session opens on its own — the Rock &amp; Pop day above is the current one.
          </MyTextConstructor>
        </div>
      </div>
    </MyPhotoSection>

    <TabCrossRef
      title="Looking for exam fees?"
      button-label="View all exam fees"
      :icon="PoundSterling"
      :visible="props.crossRefVisible"
      @go="emit('showFees')"
    >
      Graded exams from <span class="font-medium text-brand-text">£{{ fromDigital }}</span> (digital) or
      <span class="font-medium text-brand-text">£{{ fromFaceToFace }}</span> (face-to-face).
      Theory from <span class="font-medium text-brand-text">£{{ fromTheory }}</span>.
    </TabCrossRef>
  </div>
</template>
