<!-- resources/js/pages/ExamFees.vue -->
<script setup lang="ts">
import { ref, computed, onMounted, watch, nextTick, Transition } from 'vue'
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
const activeTab = ref<'fees' | 'dates'>('fees')
const crossRefVisible = ref(true)
const headerVisible = ref(true)
const tabContentVisible = ref(true)

/* ── Reactive header text based on active tab ── */
const pageTitle = computed(() =>
  activeTab.value === 'fees'
    ? 'How much does a Trinity exam cost?'
    : 'When are the Trinity exam dates?'
)
const pageSubtitle = computed(() =>
  activeTab.value === 'fees'
    ? 'Official Trinity College London fees for 2026. The fees are set by Trinity and are exactly the same whether you use centre code 120 or book directly — no extra cost.'
    : 'Face-to-face exam sessions for 2026 in Liverpool and Wirral, plus booking window dates. Digital exams can be booked and submitted at any time.'
)

/* ── URL hash support: /exam-fees#dates switches tab ── */
onMounted(() => {
  if (window.location.hash === '#dates') activeTab.value = 'dates'
  if (window.location.hash === '#fees') activeTab.value = 'fees'
})

/* ── Switch tab and scroll to top ── */
function switchTab(tab: 'fees' | 'dates') {
  activeTab.value = tab
  nextTick(() => {
    window.scrollTo({ top: 0, behavior: 'smooth' })
  })
}

/* ── Fade transitions on tab switch ── */
watch(activeTab, (tab) => {
  window.history.replaceState(null, '', `#${tab}`)

  // Fade out header text, tab content, and cross-ref button
  headerVisible.value = false
  tabContentVisible.value = false
  crossRefVisible.value = false

  // Fade header and content back in after a longer pause (let fade-out complete)
  setTimeout(() => {
    headerVisible.value = true
    tabContentVisible.value = true
  }, 400)

  // Cross-ref button fades in later to prevent ping-pong
  setTimeout(() => {
    crossRefVisible.value = true
  }, 1200)
})

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
]

const digitalData = [
  { grade: 'Initial', fee: '£49.00' },
  { grade: 'Grade 1', fee: '£55.00' },
  { grade: 'Grade 2', fee: '£61.00' },
  { grade: 'Grade 3', fee: '£68.00' },
  { grade: 'Grade 4', fee: '£78.00' },
  { grade: 'Grade 5', fee: '£88.00' },
  { grade: 'Grade 6', fee: '£98.00' },
  { grade: 'Grade 7', fee: '£109.00' },
  { grade: 'Grade 8', fee: '£120.00' },
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
    <section class="bg-brand-surface pt-24 pb-6 md:pt-28 lg:pt-28">
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

          <div
            :class="animClass('fade-up', 1)"
            class="transition-all duration-700 ease-in-out"
            :style="{ opacity: headerVisible ? 1 : 0, transform: headerVisible ? 'translateY(0)' : 'translateY(10px)' }"
          >
            <MyTextConstructor
              variant="heading"
              fontFamily="display"
              alignment="center"
              spacing="tight"
              class="mt-3 md:!text-3xl lg:!text-4xl"
            >
              <template #myTitle>{{ pageTitle }}</template>
            </MyTextConstructor>
          </div>

          <div
            :class="animClass('fade-up', 2)"
            class="transition-all duration-700 ease-in-out delay-150"
            :style="{ opacity: headerVisible ? 1 : 0, transform: headerVisible ? 'translateY(0)' : 'translateY(10px)' }"
          >
            <p class="mx-auto mt-4 max-w-2xl text-base text-brand-text-soft sm:text-base md:text-lg lg:text-xl">
              {{ pageSubtitle }}
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- DIGITAL SAVINGS CALLOUT -->
    <section class="bg-gradient-to-r from-brand-primary via-brand-accent to-brand-primary">
      <div class="mx-auto max-w-4xl px-4 py-5 sm:px-6 sm:py-6">
        <div :class="animClass('fade-up', 3)">
          <p class="text-center text-base leading-snug text-white sm:text-base md:text-lg lg:text-xl">
            <span class="font-semibold">Digital exams are generally lower in cost than face-to-face</span>
            and carry the same Ofqual-regulated certificate and UCAS points. Both options are fully supported through centre 120.
          </p>
        </div>
      </div>
    </section>

    <!-- TAB NAVIGATION -->
    <section class="sticky top-0 z-30 border-b border-brand-border bg-brand-surface shadow-sm">
      <div class="mx-auto flex max-w-4xl items-center justify-center gap-2 px-4 py-3 sm:px-6">
        <button
          @click="activeTab = 'fees'"
          :class="[
            'flex items-center gap-2 rounded-full px-6 py-2.5 text-sm font-semibold transition-all duration-200 sm:text-base',
            activeTab === 'fees'
              ? 'bg-gradient-to-r from-brand-primary to-brand-accent text-white shadow-md'
              : 'bg-brand-surface-soft text-brand-text-soft hover:text-brand-text hover:bg-brand-border'
          ]"
        >
          <PoundSterling class="h-4 w-4" />
          Exam Fees
        </button>
        <button
          @click="activeTab = 'dates'"
          :class="[
            'flex items-center gap-2 rounded-full px-6 py-2.5 text-sm font-semibold transition-all duration-200 sm:text-base',
            activeTab === 'dates'
              ? 'bg-gradient-to-r from-brand-primary to-brand-accent text-white shadow-md'
              : 'bg-brand-surface-soft text-brand-text-soft hover:text-brand-text hover:bg-brand-border'
          ]"
        >
          <Calendar class="h-4 w-4" />
          Exam Dates
        </button>
      </div>
    </section>

    <!-- ═══ FEES TAB ═══ -->
    <div
      v-show="activeTab === 'fees'"
      class="transition-all duration-700 ease-in-out"
      :style="{ opacity: tabContentVisible ? 1 : 0, transform: tabContentVisible ? 'translateY(0)' : 'translateY(12px)' }"
    >

    <!-- FACE-TO-FACE FEES -->
    <section
      class="relative bg-cover bg-center bg-no-repeat"
      style="background-image: url('https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/blue_BG_5.jpg')"
    >
      <div class="absolute inset-0 bg-black/50"></div>
      <div class="relative mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            textColor="text-white"
            class="md:!text-2xl lg:!text-3xl"
          >
            <template #myTitle>Face-to-face exam fees</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-2xl text-center text-base text-white/80 sm:text-base md:text-lg lg:text-xl">
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
    <section
      class="relative bg-cover bg-center bg-no-repeat"
      style="background-image: url('https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/blue_BG_8.jpg')"
    >
      <div class="absolute inset-0 bg-black/50"></div>
      <div class="relative mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            textColor="text-white"
            class="md:!text-2xl lg:!text-3xl"
          >
            <template #myTitle>Digital exam fees</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-2xl text-center text-base text-white/80 sm:text-base md:text-lg lg:text-xl">
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

      </div>
    </section>

    <!-- THEORY FEES -->
    <section
      class="relative bg-cover bg-center bg-no-repeat"
      style="background-image: url('https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/blue_BG_10.jpg')"
    >
      <div class="absolute inset-0 bg-black/50"></div>
      <div class="relative mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            textColor="text-white"
            class="md:!text-2xl lg:!text-3xl"
          >
            <template #myTitle>Theory exam fees</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-2xl text-center text-base text-white/80 sm:text-base md:text-lg lg:text-xl">
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
    <section
      class="relative bg-cover bg-center bg-no-repeat"
      style="background-image: url('https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/blue_BG_6.jpg')"
    >
      <div class="absolute inset-0 bg-black/50"></div>
      <div class="relative mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            textColor="text-white"
            class="md:!text-2xl lg:!text-3xl"
          >
            <template #myTitle>Diploma fees</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-2xl text-center text-base text-white/80 sm:text-base md:text-lg lg:text-xl">
            For candidates beyond Grade 8. Associate (ATCL), Licentiate (LTCL) and Fellowship (FTCL) diplomas.
          </p>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-8">
          <p class="mb-3 text-lg font-semibold text-white">Face-to-face diplomas</p>
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
          <p class="mb-3 text-lg font-semibold text-white">Digital diplomas <span class="text-sm font-normal text-brand-accent">(new for 2026)</span></p>
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

    <!-- QUICK DATES REFERENCE (bottom of fees tab) -->
    <section class="bg-brand-surface-soft">
      <div class="mx-auto max-w-4xl px-4 py-10 sm:px-6">
        <div
          class="text-center transition-all duration-700 ease-out"
          :class="crossRefVisible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4 pointer-events-none'"
        >
          <div class="mx-auto mb-3 h-1 w-16 rounded-full bg-gradient-to-r from-brand-primary to-brand-accent"></div>
          <p class="text-lg font-semibold text-brand-text sm:text-xl">Looking for exam dates?</p>
          <p class="mx-auto mt-2 max-w-xl text-sm text-brand-text-soft sm:text-base">
            Three face-to-face sessions per year: <span class="font-medium text-brand-text">March</span>,
            <span class="font-medium text-brand-text">July</span> and
            <span class="font-medium text-brand-text">November</span>.
            Digital exams can be booked any time.
          </p>
          <button
            @click="switchTab('dates')"
            :disabled="!crossRefVisible"
            class="mt-4 inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-brand-primary to-brand-accent px-6 py-2.5 text-sm font-semibold text-white shadow-md transition-all duration-200 hover:opacity-90 sm:text-base"
          >
            <Calendar class="h-4 w-4" />
            View all exam dates
          </button>
        </div>
      </div>
    </section>

    </div><!-- end FEES TAB -->

    <!-- ═══ DATES TAB ═══ -->
    <div
      v-show="activeTab === 'dates'"
      class="transition-all duration-700 ease-in-out"
      :style="{ opacity: tabContentVisible ? 1 : 0, transform: tabContentVisible ? 'translateY(0)' : 'translateY(12px)' }"
    >

    <!-- EXAM DATES -->
    <section
      id="dates"
      class="relative bg-cover bg-center bg-no-repeat"
      style="background-image: url('https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/blue_BG_7.jpg')"
    >
      <div class="absolute inset-0 bg-black/50"></div>
      <div class="relative mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
          <div class="mx-auto mb-4 h-1 w-16 rounded-full bg-white/30"></div>
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            textColor="text-white"
            class="md:!text-2xl lg:!text-3xl"
          >
            <template #myTitle>Face-to-face exam dates 2026</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-2xl text-center text-base text-white/80 sm:text-base md:text-lg lg:text-xl">
            Liverpool and Wirral — three sessions per year for both Classical &amp; Jazz and Rock &amp; Pop.
            Digital exams can be booked and submitted at any time.
          </p>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-8">
          <p class="mb-3 text-lg font-semibold text-white">Exam weeks and closing dates</p>
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
          <p class="mb-3 text-lg font-semibold text-white">Online booking windows</p>
          <MyTableConstructor
            :data="bookingWindowData"
            :columns="bookingWindowColumns"
            rowKey="session"
            :sortable="false"
            :striped="true"
            :bordered="true"
            size="medium"
          />
          <p class="mt-3 text-sm text-white/60">
            Booking windows are for face-to-face Classical &amp; Jazz exams via the MOB system. Rock &amp; Pop face-to-face dates are booked through MyTrinity.
          </p>
        </div>
      </div>
    </section>

    <!-- QUICK FEES REFERENCE (bottom of dates tab) -->
    <section class="bg-brand-surface-soft">
      <div class="mx-auto max-w-4xl px-4 py-10 sm:px-6">
        <div
          class="text-center transition-all duration-700 ease-out"
          :class="crossRefVisible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4 pointer-events-none'"
        >
          <div class="mx-auto mb-3 h-1 w-16 rounded-full bg-gradient-to-r from-brand-primary to-brand-accent"></div>
          <p class="text-lg font-semibold text-brand-text sm:text-xl">Looking for exam fees?</p>
          <p class="mx-auto mt-2 max-w-xl text-sm text-brand-text-soft sm:text-base">
            Graded exams from <span class="font-medium text-brand-text">£49</span> (digital) or
            <span class="font-medium text-brand-text">£55</span> (face-to-face).
            Theory from <span class="font-medium text-brand-text">£42</span>.
          </p>
          <button
            @click="switchTab('fees')"
            :disabled="!crossRefVisible"
            class="mt-4 inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-brand-primary to-brand-accent px-6 py-2.5 text-sm font-semibold text-white shadow-md transition-all duration-200 hover:opacity-90 sm:text-base"
          >
            <PoundSterling class="h-4 w-4" />
            View all exam fees
          </button>
        </div>
      </div>
    </section>

    </div><!-- end DATES TAB -->

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
            Use centre code 120 on Trinity's booking page — same fees, plus incentives, recognition and support.
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

    <MyFooter variant="gradient" />
  </div>

  <BookingModal :show="showBookingModal" @close="showBookingModal = false" />
</template>
