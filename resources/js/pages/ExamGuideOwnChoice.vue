<!-- resources/js/pages/ExamGuideOwnChoice.vue -->
<script setup lang="ts">
import { computed, ref } from 'vue'
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
import { Music, ListChecks, Mic, Clock, ShieldCheck, Scale } from 'lucide-vue-next'
import { useAccordionHashOpen } from '@/composables/useAccordionHashOpen'
import faqPage from '@/data/faqs/examGuideOwnChoice'
import { faqAccordionItems } from '@/lib/faqs'
import {
  GRADE_OVERVIEW,
  OWN_CHOICE_CHECKLIST,
  OWN_CHOICE_GRADES,
  OWN_CHOICE_INSTRUMENTS,
  OWN_CHOICE_PAPERWORK,
  OWN_CHOICE_PATH,
  OWN_CHOICE_RULES,
  gradeRows,
} from '@/data/ownChoiceSongs'

useAccordionHashOpen()

const { animClass } = usePageAnimation()
const { showBookingModal } = useBookingModal()

const pageMeta = {
  title: 'Own-Choice Songs — Trinity Rock & Pop',
  description:
    'What a Trinity Rock & Pop own-choice song can be, how long and how hard it must be at every grade, and what to bring — for guitar, bass, drums, keyboards and vocals.',
  canonicalUrl: `https://musicexams.help${OWN_CHOICE_PATH}`,
}

const breadcrumbPages = [
  { name: 'Exam Guide', href: '/exam-guide' },
  { name: 'Own-Choice Songs', href: OWN_CHOICE_PATH, current: true },
]

const ruleIcons = [Music, ListChecks, Mic, ShieldCheck, Clock, Scale]
const ruleCards = OWN_CHOICE_RULES.map((rule, index) => ({ ...rule, icon: ruleIcons[index] }))

const instrumentIndex = ref(0)
const gradeIndex = ref(0)

const instrument = computed(() => OWN_CHOICE_INSTRUMENTS[instrumentIndex.value])
const parameterRows = computed(() => gradeRows(instrument.value, gradeIndex.value))

const parameterColumns = computed(() => [
  { key: 'label', title: `${instrument.value.name} · ${OWN_CHOICE_GRADES[gradeIndex.value]}`, sortable: false, width: '35%' },
  { key: 'value', title: 'Guideline', sortable: false },
])

const overviewColumns = [
  { key: 'grade', title: 'Grade', sortable: false },
  { key: 'length', title: 'Length', sortable: false },
  { key: 'tempo', title: 'Tempo', sortable: false },
]

const paperworkColumns = [
  { key: 'topic', title: '', sortable: false },
  { key: 'faceToFace', title: 'Face-to-face', sortable: false },
  { key: 'digital', title: 'Digital', sortable: false },
]

const checklistCards = [
  {
    icon: Scale,
    title: 'Put it next to a book song and compare',
    detail: OWN_CHOICE_CHECKLIST.map((item) => `• ${item}`).join('<br>'),
  },
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
            <template #myTitle>Exam Guide · Rock &amp; Pop</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-3">
          <MyTextConstructor variant="heading" fontFamily="display" alignment="center" spacing="tight" titleTag="h1">
            <template #myTitle>Own-Choice Songs</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 3)" class="mx-auto mt-4 max-w-2xl">
          <MyTextConstructor variant="muted" alignment="center">
            <template #myPara>
              In a Trinity Rock &amp; Pop exam, song 2 can be a song you choose. Here is what it can be, how long
              and how hard it should be at every grade, and what to bring, for guitar, bass, drums, keyboards and
              vocals. There are no prerequisites: any grade can be taken without the one before it.
            </template>
          </MyTextConstructor>
        </div>
      </div>
    </section>

    <!-- RULES -->
    <section
      class="relative bg-cover bg-center bg-no-repeat"
      style="background-image: url('https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/blue_BG_7.jpg')"
    >
      <div class="absolute inset-0 bg-black/50"></div>
      <div class="relative mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor variant="subheading" fontFamily="display" alignment="center" spacing="tight" textColor="text-white">
            <template #myTitle>The rules</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-8">
          <MyGlassCardConstructor :cards="ruleCards" :columns="2" :show-footer="false" />
        </div>
      </div>
    </section>

    <!-- PARAMETERS BY INSTRUMENT AND GRADE -->
    <section id="parameters" class="bg-brand-surface scroll-mt-32">
      <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor variant="subheading" fontFamily="display" alignment="center" spacing="tight">
            <template #myTitle>What each grade asks for</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 2)" class="mx-auto mt-4 max-w-2xl">
          <MyTextConstructor variant="muted" alignment="center">
            <template #myPara>
              Trinity’s own-choice parameters, word for word from the syllabus. Each grade lists only what is new, and
              everything from the grades below still counts.
            </template>
          </MyTextConstructor>
        </div>

        <div class="mt-8 flex flex-wrap justify-center gap-2" role="group" aria-label="Instrument">
          <MyButtonConstructor
            v-for="(item, index) in OWN_CHOICE_INSTRUMENTS"
            :key="item.key"
            size="medium"
            :variant="index === instrumentIndex ? 'secondary' : 'outline'"
            :aria-pressed="index === instrumentIndex"
            @click="instrumentIndex = index"
          >
            {{ item.name }}
          </MyButtonConstructor>
        </div>

        <div class="mt-4 flex flex-wrap justify-center gap-2" role="group" aria-label="Grade">
          <MyButtonConstructor
            v-for="(grade, index) in OWN_CHOICE_GRADES"
            :key="grade"
            size="small"
            :variant="index === gradeIndex ? 'secondary' : 'outline'"
            :aria-pressed="index === gradeIndex"
            @click="gradeIndex = index"
          >
            {{ grade }}
          </MyButtonConstructor>
        </div>

        <MyTableConstructor
          :data="parameterRows"
          :columns="parameterColumns"
          rowKey="label"
          :sortable="false"
          :striped="true"
          :bordered="true"
          size="medium"
        />

        <MyCardConstructor v-if="instrument.notes.length" :title="`${instrument.name}: also worth knowing`" padding="large">
          <div class="flex flex-col gap-3">
            <MyTextConstructor v-for="note in instrument.notes" :key="note" variant="muted">
              <template #myPara>{{ note }}</template>
            </MyTextConstructor>
          </div>
        </MyCardConstructor>
      </div>
    </section>

    <!-- LENGTH AND TEMPO AT A GLANCE -->
    <section
      class="relative bg-cover bg-center bg-no-repeat"
      style="background-image: url('https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/blue_BG_9.jpg')"
    >
      <div class="absolute inset-0 bg-black/50"></div>
      <div class="relative mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor variant="subheading" fontFamily="display" alignment="center" spacing="tight" textColor="text-white">
            <template #myTitle>Length and tempo, every grade</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 2)" class="mx-auto mt-4 max-w-2xl">
          <MyTextConstructor variant="muted" alignment="center" textColor="text-white/80">
            <template #myPara>The same on all five instruments.</template>
          </MyTextConstructor>
        </div>

        <MyTableConstructor
          :data="GRADE_OVERVIEW"
          :columns="overviewColumns"
          rowKey="grade"
          :sortable="false"
          :striped="true"
          :bordered="true"
          size="medium"
        />
      </div>
    </section>

    <!-- HOW TO CHECK A SONG -->
    <section class="bg-black">
      <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor variant="subheading" fontFamily="display" alignment="center" spacing="tight" textColor="text-white">
            <template #myTitle>Is my song the right standard?</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 2)" class="mx-auto mt-4 max-w-2xl">
          <MyTextConstructor variant="muted" alignment="center" textColor="text-white/80">
            <template #myPara>
              Nobody checks it in advance, so check it yourself. Trinity’s advice: a suitable own-choice song will
              probably look and feel like one of the songs in the book for the grade.
            </template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 3)" class="mt-8">
          <MyGlassCardConstructor :cards="checklistCards" :columns="1" :show-footer="false" />
        </div>
      </div>
    </section>

    <!-- WHAT TO BRING OR UPLOAD -->
    <section class="bg-brand-surface">
      <div class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor variant="subheading" fontFamily="display" alignment="center" spacing="tight">
            <template #myTitle>What to bring or upload</template>
          </MyTextConstructor>
        </div>

        <MyTableConstructor
          :data="OWN_CHOICE_PAPERWORK"
          :columns="paperworkColumns"
          rowKey="topic"
          :sortable="false"
          :striped="true"
          :bordered="true"
          :stackFromColumns="3"
          size="medium"
        />
      </div>
    </section>

    <!-- FAQ -->
    <section class="bg-black">
      <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor variant="subheading" fontFamily="display" alignment="center" spacing="tight" textColor="text-white">
            <template #myTitle>Own-choice questions</template>
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
            <template #myTitle>Ready to book?</template>
          </MyTextConstructor>
        </div>

        <div class="mt-6 flex flex-wrap items-center justify-center gap-4">
          <MyButtonConstructor variant="primary" size="large" @click="showBookingModal = true">
            Book Your Exam
          </MyButtonConstructor>
          <a href="/exam-guide/syllabuses?from=own-choice-songs#guitar">
            <MyButtonConstructor variant="outline" size="large">
              Rock &amp; Pop Syllabuses
            </MyButtonConstructor>
          </a>
          <a href="/exam-guide?from=own-choice-songs">
            <MyButtonConstructor variant="outline" size="large">
              Back to Exam Guide
            </MyButtonConstructor>
          </a>
        </div>
      </div>
    </section>

    <MyFooter variant="gradient" />

    <BookingModal :show="showBookingModal" @close="showBookingModal = false" />
  </div>
</template>
