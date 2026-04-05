<!-- resources/js/pages/ExamFees.vue -->
<script setup lang="ts">
import { ref } from 'vue'
import { usePageAnimation } from '@/composables/usePageAnimation'
import Head from '@/components/layouts/Head.vue'
import Navbar from '@/components/layouts/Navbar.vue'
import Breadcrumbs from '@/components/layouts/Breadcrumbs.vue'
import BookingModal from '@/components/BookingModal.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MyTableConstructor from '@/components/reusables/MyTableConstructor.vue'
import MyAccordionConstructor from '@/components/reusables/MyAccordionConstructor.vue'
import MyFooter from '@/components/layouts/MyFooter.vue'
import { PoundSterling, Calendar, FileText } from 'lucide-vue-next'

const { animClass } = usePageAnimation()
const showBookingModal = ref(false)

const pageMeta = {
  title: 'Exam Fees 2026 — musicExams.help',
  description:
    'Trinity College London exam fees for 2026. Face-to-face, digital and theory prices for all grades from Initial to Grade 8, plus diploma fees.',
}

const breadcrumbPages = [
  { name: 'Exam Fees', href: '/exam-fees', current: true },
]

/* ── Face-to-face fees ── */
const f2fColumns = [
  { key: 'grade', title: 'Grade', sortable: false },
  { key: 'fee', title: 'Fee (GBP)', sortable: false, align: 'right' as const },
]

const f2fData = [
  { grade: 'Initial', fee: '£55.00' },
  { grade: 'Grade 1', fee: '£61.00' },
  { grade: 'Grade 2', fee: '£68.00' },
  { grade: 'Grade 3', fee: '£76.00' },
  { grade: 'Grade 4', fee: '£86.00' },
  { grade: 'Grade 5', fee: '£99.00' },
  { grade: 'Grade 6', fee: '£109.00' },
  { grade: 'Grade 7', fee: '£122.00' },
  { grade: 'Grade 8', fee: '£138.00' },
]

/* ── Digital fees ── */
const digitalColumns = [
  { key: 'grade', title: 'Grade', sortable: false },
  { key: 'fee', title: 'Fee (GBP)', sortable: false, align: 'right' as const },
  { key: 'saving', title: 'Saving vs F2F', sortable: false, align: 'right' as const },
]

const digitalData = [
  { grade: 'Initial', fee: '£49.00', saving: '£6.00' },
  { grade: 'Grade 1', fee: '£55.00', saving: '£6.00' },
  { grade: 'Grade 2', fee: '£61.00', saving: '£7.00' },
  { grade: 'Grade 3', fee: '£68.00', saving: '£8.00' },
  { grade: 'Grade 4', fee: '£78.00', saving: '£8.00' },
  { grade: 'Grade 5', fee: '£88.00', saving: '£11.00' },
  { grade: 'Grade 6', fee: '£98.00', saving: '£11.00' },
  { grade: 'Grade 7', fee: '£109.00', saving: '£13.00' },
  { grade: 'Grade 8', fee: '£120.00', saving: '£18.00' },
]

/* ── Theory fees ── */
const theoryColumns = [
  { key: 'grade', title: 'Grade', sortable: false },
  { key: 'fee', title: 'Fee (GBP)', sortable: false, align: 'right' as const },
]

const theoryData = [
  { grade: 'Grade 1', fee: '£42.00' },
  { grade: 'Grade 2', fee: '£44.00' },
  { grade: 'Grade 3', fee: '£48.00' },
  { grade: 'Grade 4', fee: '£52.00' },
  { grade: 'Grade 5', fee: '£57.00' },
  { grade: 'Grade 6', fee: '£62.00' },
  { grade: 'Grade 7', fee: '£69.00' },
  { grade: 'Grade 8', fee: '£74.00' },
]

/* ── Diploma fees ── */
const diplomaF2fColumns = [
  { key: 'diploma', title: 'Diploma', sortable: false },
  { key: 'unit', title: 'Unit', sortable: false },
  { key: 'fee', title: 'Fee (GBP)', sortable: false, align: 'right' as const },
]

const diplomaF2fData = [
  { diploma: 'ATCL Recital', unit: '—', fee: '£333.00' },
  { diploma: 'ATCL Principles (Instrumental/Vocal Teaching)', unit: 'Unit 1', fee: '£197.00' },
  { diploma: 'ATCL Principles (Instrumental/Vocal Teaching)', unit: 'Unit 2', fee: '£215.00' },
  { diploma: 'AMusTCL (Theory)', unit: '—', fee: '£149.00' },
  { diploma: 'LTCL Recital', unit: '—', fee: '£468.00' },
  { diploma: 'LTCL Music Teaching', unit: 'Unit 1', fee: '£277.00' },
  { diploma: 'LTCL Music Teaching', unit: 'Unit 2', fee: '£542.00' },
  { diploma: 'LTCL Principles (Instrumental/Vocal Teaching)', unit: 'Unit 1', fee: '£277.00' },
  { diploma: 'LTCL Principles (Instrumental/Vocal Teaching)', unit: 'Unit 2', fee: '£507.00' },
  { diploma: 'LMusTCL (Theory)', unit: '—', fee: '£200.00' },
  { diploma: 'FTCL Recital', unit: '—', fee: '£660.00' },
]

const diplomaDigitalColumns = [
  { key: 'diploma', title: 'Diploma', sortable: false },
  { key: 'fee', title: 'Fee (GBP)', sortable: false, align: 'right' as const },
]

const diplomaDigitalData = [
  { diploma: 'ATCL Recital', fee: '£292.00' },
  { diploma: 'LTCL Recital', fee: '£379.00' },
  { diploma: 'FTCL Recital', fee: '£576.00' },
]

/* ── Exam dates ── */
const dateColumns = [
  { key: 'session', title: 'Exam Week', sortable: false },
  { key: 'closing', title: 'Booking Closes', sortable: false },
  { key: 'note', title: 'Note', sortable: false },
]

const dateData = [
  { session: 'Week of 2nd March 2026', closing: '2nd February 2026', note: 'Rock & Pop usually Saturday' },
  { session: 'Week of 6th July 2026', closing: '6th June 2026', note: 'Rock & Pop usually Saturday' },
  { session: 'Week of 30th November 2026', closing: '30th October 2026', note: 'Rock & Pop usually Saturday' },
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

/* ── FAQs ── */
const faqs = [
  {
    id: 1,
    question: 'Are there any extra costs beyond the exam fee?',
    answer: 'All fees include a digital certificate that can be downloaded or shared online. If you want a printed paper certificate, that costs an additional £5.00 for UK delivery. There are no other hidden charges.',
  },
  {
    id: 2,
    question: 'Why are digital exams cheaper than face-to-face?',
    answer: 'Digital exams don\'t require venue hire, an examiner travelling to a location, or scheduled session dates — so Trinity passes the saving on. You get exactly the same Ofqual-regulated certificate and UCAS points either way.',
  },
  {
    id: 3,
    question: 'Do I pay more by booking through centre 120?',
    answer: 'No. The exam fees are set by Trinity College London and are the same regardless of which centre you book through. Centre 120 simply gives you access to additional benefits, recognition and support at no extra cost.',
  },
  {
    id: 4,
    question: 'Are there late entry surcharges?',
    answer: 'Yes — Trinity applies surcharges for late entries. Check Trinity\'s terms and conditions for current late entry fees. Booking well ahead of the closing date avoids this.',
  },
  {
    id: 5,
    question: 'Are there group or centre fees?',
    answer: 'Registered Exam Centres have a minimum session fee of £867 for face-to-face exam sessions. There are also discounted paper certificate fees for centres purchasing in bulk. These are handled automatically — you don\'t need to worry about them when booking.',
  },
  {
    id: 6,
    question: 'Do Trinity require a theory exam before higher grades?',
    answer: 'No. Unlike some other exam boards, Trinity has no theory requirement at any grade. You can go straight to Grade 8 without ever sitting a theory exam. Theory exams are available separately if you want them.',
  },
]
</script>

<template>
  <Head :title="pageMeta.title" :description="pageMeta.description" />

  <div class="min-h-screen bg-black text-brand-text">
    <Navbar />

    <!-- HEADER -->
    <section class="bg-brand-surface pt-24 pb-10 md:pt-28 lg:pt-28">
      <div class="mx-auto max-w-4xl px-4 sm:px-6">
        <div class="mb-6">
          <Breadcrumbs :pages="breadcrumbPages" home-href="/" />
        </div>
        <div class="text-center">
          <div :class="animClass('fade-up', 0)">
            <MyTextConstructor variant="eyebrow" alignment="center" spacing="tight">
              <template #myTitle>Exam Fees &amp; Dates</template>
            </MyTextConstructor>
          </div>

          <div :class="animClass('fade-up', 1)">
            <MyTextConstructor
              variant="heading"
              fontFamily="display"
              alignment="center"
              spacing="tight"
              class="mt-3 md:!text-3xl lg:!text-4xl"
            >
              <template #myTitle>How much does a Trinity exam cost?</template>
            </MyTextConstructor>
          </div>

          <div :class="animClass('fade-up', 2)">
            <p class="mx-auto mt-4 max-w-2xl text-base text-brand-text-soft sm:text-base md:text-lg lg:text-xl">
              Official Trinity College London fees for 2026. Digital exams are cheaper than face-to-face at every grade.
              Booking through centre 120 costs nothing extra — you pay the same as booking directly.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- FACE-TO-FACE FEES -->
    <section class="border-t border-brand-border bg-brand-bg">
      <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            class="md:!text-2xl lg:!text-3xl"
          >
            <template #myTitle>Face-to-face exam fees</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-2xl text-center text-base text-brand-text-soft sm:text-base md:text-lg lg:text-xl">
            Classical &amp; Jazz and Rock &amp; Pop — same fees for both.
            Exams take place at our centres in Liverpool and Wirral.
          </p>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-8">
          <MyTableConstructor
            :data="f2fData"
            :columns="f2fColumns"
            rowKey="grade"
            :sortable="false"
            :striped="true"
            :bordered="true"
            size="medium"
          />
        </div>
      </div>
    </section>

    <!-- DIGITAL FEES -->
    <section class="bg-brand-surface">
      <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            class="md:!text-2xl lg:!text-3xl"
          >
            <template #myTitle>Digital exam fees</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-2xl text-center text-base text-brand-text-soft sm:text-base md:text-lg lg:text-xl">
            Classical &amp; Jazz and Rock &amp; Pop — same fees for both.
            Record anywhere and submit online.
          </p>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-8">
          <MyTableConstructor
            :data="digitalData"
            :columns="digitalColumns"
            rowKey="grade"
            :sortable="false"
            :striped="true"
            :bordered="true"
            size="medium"
          />
        </div>

        <div :class="animClass('fade-up', 3)" class="mt-6 rounded-2xl border border-brand-accent/30 bg-brand-accent/5 p-5 sm:p-6">
          <p class="text-base leading-snug text-brand-text-soft sm:text-base md:text-lg">
            <span class="font-semibold text-brand-accent">Digital exams save you money at every grade.</span>
            At Grade 8 the saving is £18 compared to face-to-face — and you get the same Ofqual-regulated certificate and UCAS points.
          </p>
        </div>
      </div>
    </section>

    <!-- THEORY FEES -->
    <section class="border-t border-brand-border bg-brand-bg">
      <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            class="md:!text-2xl lg:!text-3xl"
          >
            <template #myTitle>Theory exam fees</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-2xl text-center text-base text-brand-text-soft sm:text-base md:text-lg lg:text-xl">
            Digital only. No Initial grade for theory — starts at Grade 1.
            Trinity does not require theory for practical exams at any grade.
          </p>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-8">
          <MyTableConstructor
            :data="theoryData"
            :columns="theoryColumns"
            rowKey="grade"
            :sortable="false"
            :striped="true"
            :bordered="true"
            size="medium"
          />
        </div>
      </div>
    </section>

    <!-- DIPLOMA FEES -->
    <section class="bg-brand-surface">
      <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            class="md:!text-2xl lg:!text-3xl"
          >
            <template #myTitle>Diploma fees</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-2xl text-center text-base text-brand-text-soft sm:text-base md:text-lg lg:text-xl">
            For candidates beyond Grade 8. Associate (ATCL), Licentiate (LTCL) and Fellowship (FTCL) diplomas.
          </p>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-8">
          <p class="mb-3 text-lg font-semibold text-brand-text">Face-to-face diplomas</p>
          <MyTableConstructor
            :data="diplomaF2fData"
            :columns="diplomaF2fColumns"
            rowKey="diploma"
            :sortable="false"
            :striped="true"
            :bordered="true"
            size="medium"
          />
        </div>

        <div :class="animClass('fade-up', 3)" class="mt-10">
          <p class="mb-3 text-lg font-semibold text-brand-text">Digital diplomas <span class="text-sm font-normal text-brand-accent">(new for 2026)</span></p>
          <MyTableConstructor
            :data="diplomaDigitalData"
            :columns="diplomaDigitalColumns"
            rowKey="diploma"
            :sortable="false"
            :striped="true"
            :bordered="true"
            size="medium"
          />
        </div>
      </div>
    </section>

    <!-- EXAM DATES -->
    <section id="dates" class="border-t border-brand-border bg-brand-bg">
      <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            class="md:!text-2xl lg:!text-3xl"
          >
            <template #myTitle>Face-to-face exam dates 2026</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-2xl text-center text-base text-brand-text-soft sm:text-base md:text-lg lg:text-xl">
            Liverpool and Wirral — three sessions per year for both Classical &amp; Jazz and Rock &amp; Pop.
            Digital exams can be booked and submitted at any time.
          </p>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-8">
          <p class="mb-3 text-lg font-semibold text-brand-text">Exam weeks and closing dates</p>
          <MyTableConstructor
            :data="dateData"
            :columns="dateColumns"
            rowKey="session"
            :sortable="false"
            :striped="true"
            :bordered="true"
            size="medium"
          />
        </div>

        <div :class="animClass('fade-up', 3)" class="mt-10">
          <p class="mb-3 text-lg font-semibold text-brand-text">Online booking windows</p>
          <MyTableConstructor
            :data="bookingWindowData"
            :columns="bookingWindowColumns"
            rowKey="session"
            :sortable="false"
            :striped="true"
            :bordered="true"
            size="medium"
          />
          <p class="mt-3 text-sm text-brand-text-soft">
            Booking windows are for face-to-face Classical &amp; Jazz exams via the MOB system. Rock &amp; Pop face-to-face dates are booked through MyTrinity.
          </p>
        </div>
      </div>
    </section>

    <!-- CTA -->
    <section class="bg-gradient-to-r from-brand-primary via-brand-accent to-brand-primary">
      <div class="mx-auto max-w-4xl px-4 py-10 text-center sm:px-6">
        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor
            variant="button-lg"
            alignment="center"
            spacing="tight"
            textColor="text-brand-text-inverse"
          >
            <template #myTitle>Ready to book?</template>
          </MyTextConstructor>
          <p class="mx-auto mt-2 max-w-xl text-base text-white/80 sm:text-base md:text-lg">
            Same fees as booking directly — plus incentives, recognition and support through centre 120.
          </p>
        </div>
        <div :class="animClass('fade-up', 2)" class="mt-6">
          <MyButtonConstructor variant="light" size="large" @click="showBookingModal = true">
            Book Your Exam
          </MyButtonConstructor>
        </div>
      </div>
    </section>

    <!-- FAQ -->
    <section class="bg-black">
      <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            textColor="text-white"
            class="md:!text-2xl lg:!text-3xl"
          >
            <template #myTitle>Common questions about fees</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-8">
          <MyAccordionConstructor
            :items="faqs"
            size="small"
            header-bg-color="bg-gradient-to-r from-brand-primary via-brand-accent to-brand-primary"
            header-text-color="text-brand-text-inverse"
            header-hover-bg-color="hover:opacity-90"
            border-color="border-brand-primary"
            content-bg-color="bg-brand-surface"
          />
        </div>

        <div :class="animClass('fade-up', 3)" class="mt-8 text-center">
          <p class="text-sm text-white/60">
            Fees shown are from the official Trinity College London fee schedule for 1 January – 31 December 2026 and may be subject to change.
          </p>
        </div>
      </div>
    </section>

    <MyFooter />
  </div>

  <BookingModal :show="showBookingModal" @close="showBookingModal = false" />
</template>
