<!-- resources/js/pages/ExamFees.vue -->
<script setup lang="ts">
// /exam-fees: Trinity's fees and exam dates on two tabs. The page holds the
// state (tab, fee year) and the header, CTA and FAQ; each tab is its own
// component in components/examfees/.
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { usePageAnimation } from '@/composables/usePageAnimation'
import { useAccordionHashOpen } from '@/composables/useAccordionHashOpen'
import { useBookingModal } from '@/composables/useBookingModal'
import Head from '@/components/layouts/Head.vue'
import Navbar from '@/components/layouts/Navbar.vue'
import Breadcrumbs from '@/components/layouts/Breadcrumbs.vue'
import BookingModal from '@/components/BookingModal.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MyAccordionConstructor from '@/components/reusables/MyAccordionConstructor.vue'
import MyBanner from '@/components/reusables/MyBanner.vue'
import MyFooter from '@/components/layouts/MyFooter.vue'
import PageMaintenance from '@/components/reusables/PageMaintenance.vue'
import ExamFeesTabNav, { type ExamFeesTab as Tab } from '@/components/examfees/ExamFeesTabNav.vue'
import ExamFeesTab from '@/components/examfees/ExamFeesTab.vue'
import ExamDatesTab from '@/components/examfees/ExamDatesTab.vue'
import FeePosterDownload from '@/components/examfees/FeePosterDownload.vue'
import faqPage from '@/data/faqs/examFees'
import { faqAccordionItems } from '@/lib/faqs'
import { currentSchedule, schedulesOnShow, scheduleForYear, posterOnShow, formatDay } from '@/data/examFees'

const { animClass } = usePageAnimation()
const { showBookingModal } = useBookingModal()
useAccordionHashOpen()
const activeTab = ref<Tab>('fees')
const crossRefVisible = ref(true)
const headerVisible = ref(true)
const tabContentVisible = ref(true)

/* ── Fee year: opens on this year's fees, switches itself on 1 January.
   ?year=2027 opens a published future year (the link used in emails). ── */
const today = new Date()
const current = currentSchedule(today)
const feeYears = schedulesOnShow(today)
const feeYear = ref(current.year)
const fees = computed(() => scheduleForYear(feeYear.value, today))
const nextYear = feeYears.find((s) => s.year > current.year)
const posterYear = posterOnShow(today)

/* ── "2027 fees are out" banner: opens the fees tab on the new year and
   scrolls to the tables. Gone by itself once that year has started. ── */
function showNextYear() {
  if (!nextYear) return
  feeYear.value = nextYear.year
  activeTab.value = 'fees'
  nextTick(() => {
    document.getElementById('fee-years')?.scrollIntoView({ behavior: 'smooth' })
  })
}

/* ── Reactive header text based on active tab ── */
const pageTitle = computed(() =>
  activeTab.value === 'fees'
    ? 'How much does a Trinity exam cost?'
    : 'When are the Trinity exam dates?'
)
const pageSubtitle = computed(() =>
  activeTab.value === 'fees'
    ? `Official Trinity College London fees for ${fees.value.year}. The fees are set by Trinity and are exactly the same whether you use centre 120 or book directly — no extra cost.`
    : 'Face-to-face exam sessions for 2026 in Liverpool and Wirral, plus booking window dates. Digital exams can be booked and submitted at any time.'
)

/* ── URL hash support: /exam-fees#dates switches tab ── */
onMounted(() => {
  if (window.location.hash === '#dates') activeTab.value = 'dates'
  if (window.location.hash === '#fees') activeTab.value = 'fees'
  const asked = Number(new URLSearchParams(window.location.search).get('year'))
  if (feeYears.some((s) => s.year === asked)) feeYear.value = asked
})

/* ── Switch tab and scroll to top ── */
function switchTab(tab: Tab) {
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
  title: `Trinity Exam Fees & Dates ${current.year}`,
  description:
    `Trinity College London exam fees for ${current.year} — face-to-face, digital and theory prices for every grade from Initial to Grade 8, plus diplomas and exam dates.`,
  canonicalUrl: 'https://musicexams.help/exam-fees',
}

const breadcrumbPages = computed(() => [
  { name: activeTab.value === 'dates' ? 'Exam Dates' : 'Exam Fees', href: '/exam-fees', current: true },
])

const tabStyle = computed(() => ({
  opacity: tabContentVisible.value ? 1 : 0,
  transform: tabContentVisible.value ? 'translateY(0)' : 'translateY(12px)',
}))
</script>

<template>
  <PageMaintenance pageSlug="exam-fees" pageTitle="Exam Fees & Dates">
  <Head :title="pageMeta.title" :description="pageMeta.description" :canonical-url="pageMeta.canonicalUrl" />

  <div class="min-h-screen bg-black text-brand-text">
    <Navbar />

    <!-- HEADER -->
    <section class="bg-brand-surface pt-36 pb-6 md:pt-40 lg:pt-40">
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
              titleTag="h1"
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
            <div class="mx-auto mt-4 max-w-2xl text-base md:text-lg lg:text-xl">
              <MyTextConstructor bodyVariant="inherit" alignment="center" textColor="text-brand-text-soft">{{ pageSubtitle }}</MyTextConstructor>
            </div>
          </div>
        </div>

        <div v-if="nextYear" class="mt-8" :class="animClass('fade-up', 3)">
          <MyBanner
            :text="`Trinity's ${nextYear.year} fees are out`"
            :button-text="`See ${nextYear.year} fees`"
            variant="dark"
            rounded
            @action="showNextYear"
          />
        </div>

        <div v-if="posterYear" class="mt-6" :class="animClass('fade-up', 4)">
          <FeePosterDownload :schedule="posterYear" />
        </div>
      </div>
    </section>

    <!-- DIGITAL SAVINGS CALLOUT -->
    <section class="bg-gradient-to-r from-brand-primary via-brand-accent to-brand-primary">
      <div class="mx-auto max-w-4xl px-4 py-5 sm:px-6 sm:py-6">
        <div :class="animClass('fade-up', 3)">
          <div class="text-base leading-snug md:text-lg lg:text-xl">
            <MyTextConstructor bodyVariant="inherit" alignment="center" textColor="text-white">
              <span class="font-semibold">Digital exams are generally lower in cost than face-to-face</span>
              and carry the same Ofqual-regulated certificate and UCAS points. Both options are fully supported through centre 120.
            </MyTextConstructor>
          </div>
        </div>
      </div>
    </section>

    <ExamFeesTabNav v-model="activeTab" />

    <div v-show="activeTab === 'fees'" class="transition-all duration-700 ease-in-out" :style="tabStyle">
      <ExamFeesTab
        v-model:year="feeYear"
        :fees="fees"
        :years="feeYears"
        :current-year="current.year"
        :cross-ref-visible="crossRefVisible"
        @show-dates="switchTab('dates')"
      />
    </div>

    <div v-show="activeTab === 'dates'" class="transition-all duration-700 ease-in-out" :style="tabStyle">
      <ExamDatesTab :cross-ref-visible="crossRefVisible" @show-fees="switchTab('fees')" />
    </div>

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
          <div class="mx-auto mt-2 max-w-xl text-base md:text-lg">
            <MyTextConstructor bodyVariant="inherit" alignment="center" textColor="text-white/80">
              Book through centre 120 — same fees, plus incentives, recognition and support.
            </MyTextConstructor>
          </div>
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
            :items="faqAccordionItems(faqPage)"
            size="small"
            header-bg-color="bg-gradient-to-r from-brand-primary via-brand-accent to-brand-primary"
            header-text-color="text-brand-text-inverse"
            header-hover-bg-color="hover:opacity-90"
            border-color="border-brand-primary"
            content-bg-color="bg-brand-surface"
          />
        </div>

        <div :class="animClass('fade-up', 3)" class="mt-8 text-center">
          <div class="text-sm">
            <MyTextConstructor bodyVariant="inherit" alignment="center" textColor="text-white/60">
              Fees shown are from the official Trinity College London fee schedule for {{ formatDay(fees.from) }} – {{ formatDay(fees.to) }} and may be subject to change.
            </MyTextConstructor>
          </div>
        </div>
      </div>
    </section>

    <MyFooter variant="gradient" />
  </div>

  <BookingModal :show="showBookingModal" @close="showBookingModal = false" />
  </PageMaintenance>
</template>
