<!-- resources/js/pages/ExamGuideSpecialNeeds.vue -->
<script setup lang="ts">
import { usePageAnimation } from '@/composables/usePageAnimation'
import { useBookingModal } from '@/composables/useBookingModal'
import Head from '@/components/layouts/Head.vue'
import Navbar from '@/components/layouts/Navbar.vue'
import Breadcrumbs from '@/components/layouts/Breadcrumbs.vue'
import BookingModal from '@/components/BookingModal.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MyTableConstructor from '@/components/reusables/MyTableConstructor.vue'
import MyAccordionConstructor from '@/components/reusables/MyAccordionConstructor.vue'
import MyGlassCardConstructor from '@/components/reusables/MyGlassCardConstructor.vue'
import MyCardConstructor from '@/components/reusables/MyCardConstructor.vue'
import MyFooter from '@/components/layouts/MyFooter.vue'
import { School, Laptop, PenLine, Accessibility, HeartHandshake, Shuffle, Stethoscope, Download } from 'lucide-vue-next'
import { useAccordionHashOpen } from '@/composables/useAccordionHashOpen'
import faqPage from '@/data/faqs/examGuideSpecialNeeds'
import { faqAccordionItems } from '@/lib/faqs'
import {
  SEN_ADJUSTMENTS,
  SEN_DIGITAL_ADJUSTMENTS,
  SEN_EVIDENCE,
  SEN_EXTRA_TIME,
  SEN_FORMS,
  SEN_LINKS,
  SEN_NOT_TO_CONFUSE,
  SEN_ROUTES,
  SEN_WHICH_FORM,
  SPECIAL_NEEDS_PATH,
} from '@/data/specialNeeds'

useAccordionHashOpen()

const { animClass } = usePageAnimation()
const { showBookingModal } = useBookingModal()

const pageMeta = {
  title: 'Special Needs & Access Arrangements',
  description:
    'How to ask Trinity for special needs adjustments in a music exam: which form, where to send it, the 28-day deadline, what evidence is needed and what adjustments are available.',
  canonicalUrl: `https://musicexams.help${SPECIAL_NEEDS_PATH}`,
}

const breadcrumbPages = [
  { name: 'Exam Guide', href: '/exam-guide' },
  { name: 'Special Needs', href: SPECIAL_NEEDS_PATH, current: true },
]

const whichFormColumns = [
  { key: 'exam', title: 'Exam', sortable: false },
  { key: 'form', title: 'Form', sortable: false },
  { key: 'sendTo', title: 'Send it to', sortable: false },
  { key: 'when', title: 'When', sortable: false },
]

const routeIcons = [School, Laptop, PenLine, Accessibility]
const routeCards = SEN_ROUTES.map((route, index) => ({ ...route, icon: routeIcons[index] }))

const confuseIcons = [HeartHandshake, Shuffle, Stethoscope]
const confuseCards = SEN_NOT_TO_CONFUSE.map((item, index) => ({ ...item, icon: confuseIcons[index] }))

const adjustmentColumns = [
  { key: 'adjustment', title: 'Adjustment', sortable: false },
  { key: 'what', title: 'What it is', sortable: false },
  { key: 'evidence', title: 'Evidence', sortable: false },
]

const extraTimeColumns = [
  { key: 'grades', title: 'Grades', sortable: false },
  { key: 'whole', title: 'Extra time in the exam', sortable: false },
  { key: 'unseen', title: 'Of which, unseen tests', sortable: false },
]
</script>

<template>
  <Head :title="pageMeta.title" :description="pageMeta.description" :canonical-url="pageMeta.canonicalUrl" />

  <div class="min-h-screen bg-black text-brand-text">
    <Navbar />

    <!-- HEADER -->
    <section class="bg-brand-surface pt-36 pb-10 md:pt-40 lg:pt-40">
      <div class="mx-auto max-w-4xl px-4 sm:px-6">
        <div class="mb-6">
          <Breadcrumbs :pages="breadcrumbPages" home-href="/" />
        </div>

        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor variant="eyebrow" alignment="center" spacing="tight">
            <template #myTitle>Exam Guide · SEN</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-3">
          <MyTextConstructor variant="heading" fontFamily="display" alignment="center" spacing="tight" titleTag="h1">
            <template #myTitle>Special Needs &amp; Access Arrangements</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 3)" class="mx-auto mt-4 max-w-2xl">
          <MyTextConstructor variant="muted" alignment="center">
            <template #myPara>
              Trinity can make adjustments for a candidate with a disability, a health condition or a specific
              learning need, such as extra time, large print or a briefed examiner. It all has to be asked for in
              writing, on the right form, before the exam.
            </template>
          </MyTextConstructor>
        </div>
      </div>
    </section>

    <!-- WHICH FORM -->
    <section class="bg-brand-surface">
      <div class="mx-auto max-w-5xl px-4 pb-12 sm:px-6 lg:pb-16">
        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor variant="subheading" fontFamily="display" alignment="center" spacing="tight">
            <template #myTitle>Which form, where, and when</template>
          </MyTextConstructor>
        </div>

        <MyTableConstructor
          :data="SEN_WHICH_FORM"
          :columns="whichFormColumns"
          rowKey="exam"
          :sortable="false"
          :striped="true"
          :bordered="true"
          :stackFromColumns="4"
          size="medium"
        />

        <MyTextConstructor variant="muted" alignment="center">
          <template #myPara>Download the right form from Trinity:</template>
        </MyTextConstructor>

        <div class="mt-4 flex flex-wrap justify-center gap-3">
          <a v-for="form in SEN_FORMS" :key="form.url" :href="form.url" target="_blank" rel="noopener noreferrer">
            <MyButtonConstructor variant="outline" size="medium" :icon="Download">
              {{ form.label }}
            </MyButtonConstructor>
          </a>
        </div>
      </div>
    </section>

    <!-- STEP BY STEP -->
    <section
      class="relative bg-cover bg-center bg-no-repeat"
      style="background-image: url('https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/blue_BG_7.jpg')"
    >
      <div class="absolute inset-0 bg-black/50"></div>
      <div class="relative mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor variant="subheading" fontFamily="display" alignment="center" spacing="tight" textColor="text-white">
            <template #myTitle>How to ask</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-8">
          <MyGlassCardConstructor :cards="routeCards" :columns="2" :show-footer="false" />
        </div>
      </div>
    </section>

    <!-- DON'T MIX THEM UP -->
    <section class="bg-black">
      <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor variant="subheading" fontFamily="display" alignment="center" spacing="tight" textColor="text-white">
            <template #myTitle>Three forms that get mixed up</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 2)" class="mx-auto mt-4 max-w-2xl">
          <MyTextConstructor variant="muted" alignment="center" textColor="text-white/80">
            <template #myPara>
              They sound alike but go to different places at different times.
            </template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 3)" class="mt-8">
          <MyGlassCardConstructor :cards="confuseCards" :columns="3" :show-footer="false" />
        </div>
      </div>
    </section>

    <!-- ADJUSTMENTS -->
    <section id="adjustments" class="scroll-mt-32 bg-brand-surface">
      <div class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor variant="subheading" fontFamily="display" alignment="center" spacing="tight">
            <template #myTitle>What can be adjusted: face-to-face</template>
          </MyTextConstructor>
        </div>

        <MyTableConstructor
          :data="SEN_ADJUSTMENTS"
          :columns="adjustmentColumns"
          rowKey="adjustment"
          :sortable="false"
          :striped="true"
          :bordered="true"
          :stackFromColumns="3"
          size="medium"
        />

        <MyTextConstructor variant="subheading" fontFamily="display" alignment="center" spacing="tight">
          <template #myTitle>Extra time by grade</template>
        </MyTextConstructor>

        <MyTableConstructor
          :data="SEN_EXTRA_TIME"
          :columns="extraTimeColumns"
          rowKey="grades"
          :sortable="false"
          :striped="true"
          :bordered="true"
          size="medium"
        />

        <MyTextConstructor variant="subheading" fontFamily="display" alignment="center" spacing="tight">
          <template #myTitle>What can be adjusted: digital</template>
        </MyTextConstructor>

        <MyTableConstructor
          :data="SEN_DIGITAL_ADJUSTMENTS"
          :columns="adjustmentColumns"
          rowKey="adjustment"
          :sortable="false"
          :striped="true"
          :bordered="true"
          :stackFromColumns="3"
          size="medium"
        />

        <MyCardConstructor title="About evidence" padding="large">
          <MyTextConstructor variant="muted">
            <template #myPara>{{ SEN_EVIDENCE }}</template>
          </MyTextConstructor>
        </MyCardConstructor>
      </div>
    </section>

    <!-- FAQ -->
    <section class="bg-black">
      <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor variant="subheading" fontFamily="display" alignment="center" spacing="tight" textColor="text-white">
            <template #myTitle>Special needs questions</template>
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
      </div>
    </section>

    <!-- CTA -->
    <section class="bg-brand-surface">
      <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor variant="subheading" fontFamily="display" alignment="center" spacing="tight">
            <template #myTitle>Need to talk it through?</template>
          </MyTextConstructor>
        </div>

        <div class="mt-6 flex flex-wrap items-center justify-center gap-4">
          <a :href="SEN_LINKS.overview" target="_blank" rel="noopener noreferrer">
            <MyButtonConstructor variant="primary" size="large">
              Trinity’s Special Needs Page
            </MyButtonConstructor>
          </a>
          <a href="/contact?from=special-needs">
            <MyButtonConstructor variant="outline" size="large">
              Contact Me
            </MyButtonConstructor>
          </a>
          <MyButtonConstructor variant="outline" size="large" @click="showBookingModal = true">
            Book Your Exam
          </MyButtonConstructor>
        </div>
      </div>
    </section>

    <MyFooter variant="gradient" />

    <BookingModal :show="showBookingModal" @close="showBookingModal = false" />
  </div>
</template>
