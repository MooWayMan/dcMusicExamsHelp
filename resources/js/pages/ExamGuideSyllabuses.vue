<!-- resources/js/pages/ExamGuideSyllabuses.vue -->
<script setup lang="ts">
import { ref } from 'vue'
import { usePageAnimation } from '@/composables/usePageAnimation'
import { useBookingModal } from '@/composables/useBookingModal'
import Head from '@/components/layouts/Head.vue'
import Navbar from '@/components/layouts/Navbar.vue'
import Breadcrumbs from '@/components/layouts/Breadcrumbs.vue'
import BookingModal from '@/components/BookingModal.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MyAccordionConstructor from '@/components/reusables/MyAccordionConstructor.vue'
import MyFooter from '@/components/layouts/MyFooter.vue'
import { BookOpen, ChevronDown, ExternalLink } from 'lucide-vue-next'

const { animClass } = usePageAnimation()
const { showBookingModal } = useBookingModal()

const pageMeta = {
  title: 'Syllabuses — musicExams.help',
  description:
    'Find the right Trinity College London syllabus for your instrument. Direct links to every instrument syllabus — Classical & Jazz, Rock & Pop and diplomas.',
}

const breadcrumbPages = [
  { name: 'Exam Guide', href: '/exam-guide' },
  { name: 'Syllabuses', href: '/exam-guide/syllabuses', current: true },
]

/* ── Syllabus categories ── */
interface SyllabusLink {
  title: string
  subtitle: string
  url: string
}

const classicalJazz: SyllabusLink[] = [
  {
    title: 'Piano',
    subtitle: 'Classical & Jazz, Initial to Grade 8',
    url: 'https://www.trinitycollege.com/qualifications/music/grade-exams/piano',
  },
  {
    title: 'Singing',
    subtitle: 'Classical & Jazz, Initial to Grade 8',
    url: 'https://www.trinitycollege.com/qualifications/music/grade-exams/singing',
  },
  {
    title: 'Strings',
    subtitle: 'Violin, Viola, Cello, Double Bass — Initial to Grade 8',
    url: 'https://www.trinitycollege.com/qualifications/music/grade-exams/strings',
  },
  {
    title: 'Classical Guitar',
    subtitle: 'Nylon string — Initial to Grade 8',
    url: 'https://www.trinitycollege.com/qualifications/music/grade-exams/classical-guitar',
  },
  {
    title: 'Acoustic Guitar',
    subtitle: 'Steel string, fingerstyle and plectrum — Initial to Grade 8',
    url: 'https://www.trinitycollege.com/qualifications/music/grade-exams/acoustic-guitar',
  },
  {
    title: 'Brass',
    subtitle: 'Trumpet, Horn, Trombone, Tuba and more — Initial to Grade 8',
    url: 'https://www.trinitycollege.com/qualifications/music/grade-exams/brass',
  },
  {
    title: 'Woodwind',
    subtitle: 'Flute, Clarinet, Saxophone, Oboe and more — Initial to Grade 8',
    url: 'https://www.trinitycollege.com/qualifications/music/grade-exams/woodwind',
  },
  {
    title: 'Percussion',
    subtitle: 'Drum Kit, Tuned Percussion, Snare Drum — Initial to Grade 8',
    url: 'https://www.trinitycollege.com/qualifications/music/grade-exams/percussion',
  },
  {
    title: 'Electronic Keyboard & Organ',
    subtitle: 'Initial to Grade 8',
    url: 'https://www.trinitycollege.com/qualifications/music/grade-exams/electronic-keyboard-organ',
  },
]

const rockPop: SyllabusLink[] = [
  {
    title: 'Rock & Pop Grades',
    subtitle: 'Guitar, Bass, Drums, Vocals, Keys — Initial to Grade 8',
    url: 'https://www.trinitycollege.com/qualifications/music/rock-and-pop/grades',
  },
]

const diplomas: SyllabusLink[] = [
  {
    title: 'Performance Diplomas — Classical & Jazz',
    subtitle: 'ATCL, LTCL, FTCL',
    url: 'https://www.trinitycollege.com/qualifications/music/diplomas/performance',
  },
  {
    title: 'Performance Diplomas — Rock & Pop',
    subtitle: 'ATCL, LTCL, FTCL',
    url: 'https://www.trinitycollege.com/qualifications/music/rock-and-pop/diplomas',
  },
]

/* ── Dropdown state ── */
const selectedCategory = ref<'classical' | 'rockpop' | 'diplomas'>('classical')
const dropdownOpen = ref(false)

const categories = [
  { key: 'classical' as const, label: 'Classical & Jazz Grades' },
  { key: 'rockpop' as const, label: 'Rock & Pop Grades' },
  { key: 'diplomas' as const, label: 'Diplomas' },
]

const currentLabel = () => categories.find(c => c.key === selectedCategory.value)?.label ?? ''

const currentList = (): SyllabusLink[] => {
  if (selectedCategory.value === 'classical') return classicalJazz
  if (selectedCategory.value === 'rockpop') return rockPop
  return diplomas
}

const selectCategory = (key: 'classical' | 'rockpop' | 'diplomas') => {
  selectedCategory.value = key
  dropdownOpen.value = false
}

/* ── FAQ ── */
const faqs = [
  {
    question: 'What is a syllabus?',
    answer:
      'The syllabus is Trinity\'s official document listing everything you need to know about an exam — the pieces you can choose, the scales and technical work required, the supporting tests and how the exam is marked. It\'s your roadmap for preparing.',
  },
  {
    question: 'Do I need to buy the syllabus?',
    answer:
      'No — the syllabus information is free to view on Trinity\'s website. The links on this page take you straight to the right place for your instrument. You may need to buy the sheet music for your chosen pieces, but the syllabus itself is free.',
  },
  {
    question: 'How often does the syllabus change?',
    answer:
      'Both Classical & Jazz and Rock & Pop syllabuses have no fixed end date — pieces stay valid and new ones are added regularly. Trinity always gives plenty of notice before any changes.',
  },
  {
    question: 'Can I play pieces from an older syllabus?',
    answer:
      'Yes — for both Classical & Jazz and Rock & Pop, the repertoire lists are cumulative. Pieces from previous lists are usually still valid, and new pieces are added over time. Always check the Trinity website for the latest information.',
  },
]
</script>

<template>
  <Head :title="pageMeta.title" :description="pageMeta.description" />

  <div class="min-h-screen bg-black text-brand-text">
    <Navbar />

    <!-- HEADER -->
    <section class="bg-brand-surface pt-36 pb-10 md:pt-40 lg:pt-40">
      <div class="mx-auto max-w-4xl px-4 sm:px-6">
        <div class="mb-6">
          <Breadcrumbs :pages="breadcrumbPages" home-href="/" />
        </div>

        <div :class="animClass('fade-up', 1)" class="text-center">
          <MyTextConstructor variant="eyebrow" alignment="center" spacing="tight">
            <template #myTitle>Exam Guide</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 2)">
          <MyTextConstructor
            variant="heading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            class="mt-3 md:!text-3xl lg:!text-4xl"
          >
            <template #myTitle>Syllabuses</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 3)">
          <p class="mx-auto mt-4 max-w-2xl text-center text-base text-brand-text-soft sm:text-base md:text-lg lg:text-xl">
            Find the right Trinity College London syllabus for your instrument. Each link takes you straight to Trinity's official syllabus page — no searching required.
          </p>
        </div>
      </div>
    </section>

    <!-- SYLLABUS SELECTOR -->
    <section
      class="relative"
      style="background-image: url('https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/blue_BG_5.jpg'); background-size: cover; background-position: center;"
    >
      <div class="absolute inset-0 bg-brand-primary/50" />
      <div class="relative mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            textColor="text-white"
            class="md:!text-2xl lg:!text-3xl"
          >
            <template #myTitle>Choose your exam type</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-2xl text-center text-base text-white/80 sm:text-base md:text-lg">
            Select a category below, then pick your instrument to view the full syllabus on Trinity's website.
          </p>
        </div>

        <!-- Category dropdown -->
        <div :class="animClass('fade-up', 2)" class="relative z-20 mx-auto mt-8 max-w-md">
          <div class="relative">
            <button
              type="button"
              class="flex w-full items-center justify-between rounded-xl border-4 border-brand-accent bg-white/10 px-5 py-4 text-left text-base font-semibold text-white backdrop-blur-sm transition hover:bg-white/20 sm:text-lg"
              @click="dropdownOpen = !dropdownOpen"
            >
              <span>{{ currentLabel() }}</span>
              <ChevronDown
                class="h-5 w-5 shrink-0 text-brand-accent transition-transform"
                :class="{ 'rotate-180': dropdownOpen }"
              />
            </button>

            <Transition
              enter-active-class="transition duration-200 ease-out"
              enter-from-class="opacity-0 -translate-y-2"
              enter-to-class="opacity-100 translate-y-0"
              leave-active-class="transition duration-150 ease-in"
              leave-from-class="opacity-100 translate-y-0"
              leave-to-class="opacity-0 -translate-y-2"
            >
              <div
                v-if="dropdownOpen"
                class="absolute left-0 right-0 z-10 mt-2 overflow-hidden rounded-xl border-2 border-brand-accent bg-black/95 shadow-2xl backdrop-blur-sm"
              >
                <button
                  v-for="cat in categories"
                  :key="cat.key"
                  type="button"
                  class="flex w-full items-center px-5 py-3 text-left text-base text-white transition hover:bg-brand-accent/20 sm:text-lg"
                  :class="{ 'bg-brand-accent/10 font-semibold': selectedCategory === cat.key }"
                  @click="selectCategory(cat.key)"
                >
                  {{ cat.label }}
                </button>
              </div>
            </Transition>
          </div>
        </div>

        <!-- Syllabus links list -->
        <div :class="animClass('fade-up', 3)" class="relative z-10 mt-8">
          <div class="overflow-hidden rounded-2xl border-4 border-brand-accent bg-white/10 shadow-2xl backdrop-blur-sm">
            <div class="flex items-center justify-center gap-3 bg-black px-5 py-3 sm:px-6">
              <BookOpen class="h-5 w-5 shrink-0 text-brand-accent sm:h-6 sm:w-6" />
              <p class="text-base font-semibold text-white sm:text-lg">{{ currentLabel() }}</p>
            </div>
            <div class="divide-y divide-white/10">
              <a
                v-for="item in currentList()"
                :key="item.title"
                :href="item.url"
                target="_blank"
                rel="noopener noreferrer"
                class="group flex items-center justify-between gap-4 px-5 py-4 transition hover:bg-white/10 sm:px-6"
              >
                <div>
                  <p class="text-base font-semibold text-white sm:text-lg">{{ item.title }}</p>
                  <p class="mt-0.5 text-sm text-white/60 sm:text-base">{{ item.subtitle }}</p>
                </div>
                <ExternalLink class="h-5 w-5 shrink-0 text-brand-accent transition group-hover:scale-110" />
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- HOW TO USE -->
    <section
      class="relative bg-cover bg-center bg-no-repeat"
      style="background-image: url('https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/blue_BG_8.jpg')"
    >
      <div class="absolute inset-0 bg-black/50"></div>
      <div class="relative mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            textColor="text-white"
            class="md:!text-2xl lg:!text-3xl"
          >
            <template #myTitle>How to use the syllabus</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-8 overflow-hidden rounded-2xl border-4 border-brand-accent bg-white/10 shadow-2xl backdrop-blur-sm">
          <div class="flex items-center justify-center gap-3 bg-black px-5 py-3 sm:px-6">
            <BookOpen class="h-5 w-5 shrink-0 text-brand-accent sm:h-6 sm:w-6" />
            <p class="text-base font-semibold text-white sm:text-lg">Getting started</p>
          </div>
          <div class="space-y-6 p-6">
            <div class="flex gap-4">
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-brand-primary via-brand-accent to-brand-primary text-lg font-bold text-white shadow-md sm:h-12 sm:w-12 sm:text-xl">
                1
              </div>
              <div>
                <p class="text-base font-semibold text-white sm:text-base md:text-lg">Pick your instrument</p>
                <p class="mt-1 text-base text-white/80 sm:text-base md:text-lg">Use the dropdown above to find the right syllabus for your instrument and exam type.</p>
              </div>
            </div>
            <div class="flex gap-4">
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-brand-primary via-brand-accent to-brand-primary text-lg font-bold text-white shadow-md sm:h-12 sm:w-12 sm:text-xl">
                2
              </div>
              <div>
                <p class="text-base font-semibold text-white sm:text-base md:text-lg">Find your grade</p>
                <p class="mt-1 text-base text-white/80 sm:text-base md:text-lg">On Trinity's page, select the grade you're working towards. You'll see the full list of pieces, scales and requirements.</p>
              </div>
            </div>
            <div class="flex gap-4">
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-brand-primary via-brand-accent to-brand-primary text-lg font-bold text-white shadow-md sm:h-12 sm:w-12 sm:text-xl">
                3
              </div>
              <div>
                <p class="text-base font-semibold text-white sm:text-base md:text-lg">Choose your pieces</p>
                <p class="mt-1 text-base text-white/80 sm:text-base md:text-lg">Work with your teacher to pick the pieces that suit you best. Not sure whether to go digital or face-to-face? See our <a href="/exam-guide/digital-exams?from=syllabuses" class="font-semibold text-brand-accent underline hover:opacity-70">digital exams guide</a> to help you decide.</p>
              </div>
            </div>
            <div class="flex gap-4">
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-brand-primary via-brand-accent to-brand-primary text-lg font-bold text-white shadow-md sm:h-12 sm:w-12 sm:text-xl">
                4
              </div>
              <div>
                <p class="text-base font-semibold text-white sm:text-base md:text-lg">Book your exam</p>
                <p class="mt-1 text-base text-white/80 sm:text-base md:text-lg">When you're ready, use our Book Your Exam button to find the right booking system — with centre 120 built in for digital and theory exams.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- FAQ -->
    <section class="bg-black">
      <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            textColor="text-white"
            class="md:!text-2xl lg:!text-3xl"
          >
            <template #myTitle>Syllabus questions</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-8">
          <MyAccordionConstructor
            :items="faqs.map((f, i) => ({ id: i + 1, question: f.question, answer: f.answer }))"
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
        <div :class="animClass('fade-up', 1)" class="text-center">
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            class="md:!text-2xl lg:!text-3xl"
          >
            <template #myTitle>Ready to get started?</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-xl text-base text-brand-text-soft sm:text-base md:text-lg lg:text-xl">
            Book through centre 120 and every entry earns at least a <strong>Bravo Certificate</strong> — plus the <strong>Hall of Fame</strong> and more for Merit and Distinction.
          </p>
          <div class="mt-6 flex flex-wrap items-center justify-center gap-4">
            <MyButtonConstructor variant="primary" size="large" @click="showBookingModal = true">
              Book Your Exam
            </MyButtonConstructor>
            <a href="/exam-guide?from=syllabuses">
              <MyButtonConstructor variant="outline" size="large">
                Back to Exam Guide
              </MyButtonConstructor>
            </a>
          </div>
        </div>
      </div>
    </section>

    <MyFooter variant="gradient" />

    <BookingModal :show="showBookingModal" @close="showBookingModal = false" />
  </div>
</template>
