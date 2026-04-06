<!-- resources/js/pages/ExamGuide.vue -->
<script setup lang="ts">
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { usePageAnimation } from '@/composables/usePageAnimation'
import Head from '@/components/layouts/Head.vue'
import Navbar from '@/components/layouts/Navbar.vue'
import Breadcrumbs from '@/components/layouts/Breadcrumbs.vue'
import BookingModal from '@/components/BookingModal.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MyRunnerConstructor from '@/components/reusables/MyRunnerConstructor.vue'
import MyAccordionConstructor from '@/components/reusables/MyAccordionConstructor.vue'
import MyFooter from '@/components/layouts/MyFooter.vue'
import { Music, Guitar, CheckCircle, ArrowRight, GraduationCap, BookOpen, Clock, Award } from 'lucide-vue-next'

const { animClass } = usePageAnimation()
const showBookingModal = ref(false)

const pageMeta = {
  title: 'Exam Guide — musicExams.help',
  description:
    'Your friendly guide to Trinity College London music exams. Understand Classical & Jazz vs Rock & Pop, UCAS points, what to expect on exam day and more.',
}

const breadcrumbPages = [
  { name: 'Exam Guide', href: '/exam-guide', current: true },
]

/* ── Classical & Jazz highlights ── */
const classicalHighlights = [
  'Huge range of instruments — piano, strings, woodwind, brass, singing, guitar, drums, percussion and more',
  'Three pieces from the repertoire list — one can be your own composition',
  'Huge repertoire list with no end date — pieces stay valid and new ones are added regularly',
  'Technical work — scales, arpeggios and exercises',
  'Supporting tests — sight-reading, aural, improvisation or musical knowledge',
  'Digital option replaces supporting tests with Overall Performance Criteria',
  'Repertoire-only digital pathway available — four pieces, no technical work',
]

const rockPopHighlights = [
  'Five instruments — guitar, bass, drums, keyboards and vocals',
  'Three songs from the Rock & Pop books — real songs you know from the radio',
  'One must be a Technical Focus song — a dedicated song that tests your technique',
  'Option to replace your third song with an own choice, as long as it meets the syllabus criteria',
  'Session Skills — choose between Improvising or Playback',
  'Song choices cover rock, pop, indie, funk, soul, metal and more',
  'Digital option available with the same flexibility as Classical & Jazz',
]

/* ── Shared features ── */
const sharedFeatures = [
  'Same Ofqual-regulated qualification',
  'Same certificate whether digital or face-to-face',
  'Grades from Initial (beginner) through to Grade 8 (advanced)',
  'Diplomas available beyond Grade 8',
  'UCAS points at Grades 6, 7 and 8',
  'Available as digital or face-to-face exams',
  'Marked out of 100 — Pass (60–74), Merit (75–86), Distinction (87–100)',
]

/* ── Sub-page links ── */
const guidePages = [
  {
    id: 1,
    title: 'UCAS Points',
    subTitle: 'What your exam results are worth',
    descript: 'Grades 6–8 earn UCAS points for university applications. See the full points table and important deadlines.',
    url: '/exam-guide/ucas-points',
    icon: GraduationCap,
    showIcon: true,
  },
  {
    id: 2,
    title: 'What to Expect',
    subTitle: 'Exam day — step by step',
    descript: 'What to bring, what happens in the room, how long it takes, and tips for keeping nerves in check.',
    url: '/exam-guide/what-to-expect',
    icon: Clock,
    showIcon: true,
  },
  {
    id: 3,
    title: 'Digital Exams',
    subTitle: 'Record anywhere, submit online',
    descript: 'How digital exams work, what equipment you need, how to submit, and what\'s different from face-to-face.',
    url: '/exam-guide/digital-exams',
    icon: Music,
    showIcon: true,
  },
  {
    id: 4,
    title: 'Grades Explained',
    subTitle: 'Initial to Grade 8 and beyond',
    descript: 'What each grade level means, how results are scored, and where diplomas fit in.',
    url: '/exam-guide/grades-explained',
    icon: Award,
    showIcon: true,
  },
]

/* ── FAQ ── */
const faqs = [
  {
    question: 'Can my child do both Classical & Jazz and Rock & Pop?',
    answer:
      'Yes — they are separate qualifications so a student can take exams in both. For example, a guitarist could do Classical Guitar Grade 3 and Rock & Pop Guitar Grade 2 at the same time. They count as different subjects.',
  },
  {
    question: 'Which one should we choose?',
    answer:
      'It depends on what your child enjoys playing. If they love learning from sheet music and playing classical or jazz repertoire, Classical & Jazz is the natural fit. If they prefer playing songs by bands and artists they listen to, Rock & Pop is probably more motivating. Their teacher will know which suits them best.',
  },
  {
    question: 'Are Rock & Pop exams easier than Classical & Jazz?',
    answer:
      'No. They are different, not easier. Rock & Pop exams assess different skills — groove, feel, musicality within a band context. Classical & Jazz exams focus more on technique, sight-reading and aural skills. Both are rigorous and both are regulated by Ofqual.',
  },
  {
    question: 'Do both types earn UCAS points?',
    answer:
      'Yes. Grades 6, 7 and 8 in both Classical & Jazz and Rock & Pop earn UCAS points on exactly the same scale. A Grade 8 Distinction is worth 30 UCAS points regardless of which exam type.',
  },
  {
    question: 'What if my child plays an instrument not covered by Rock & Pop?',
    answer:
      'Rock & Pop only covers guitar, bass, drums, keyboards and vocals. If your child plays a different instrument — such as flute, trumpet, violin or saxophone — they would take the Classical & Jazz exam for that instrument.',
  },
]

const handleRunnerClick = (card: { url?: string; isExternal?: boolean }) => {
  if (!card.url) return

  if (card.isExternal) {
    window.open(card.url, '_blank', 'noopener,noreferrer')
    return
  }

  router.visit(card.url)
}
</script>

<template>
  <Head :title="pageMeta.title" :description="pageMeta.description" />

  <div class="min-h-screen bg-brand-bg text-brand-text">
    <Navbar />

    <!-- HEADER -->
    <section class="bg-brand-surface pt-24 pb-10 md:pt-28 lg:pt-28">
      <div class="mx-auto max-w-4xl px-4 sm:px-6">
        <div class="mb-6">
          <Breadcrumbs :pages="breadcrumbPages" home-href="/" />
        </div>

        <div :class="animClass('fade-up', 1)" class="text-center">
          <MyTextConstructor variant="eyebrow" alignment="center" spacing="tight">
            <template #myTitle>Your Exam Guide</template>
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
            <template #myTitle>Which exam is right for you?</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 3)">
          <p class="mx-auto mt-4 max-w-2xl text-center text-base text-brand-text-soft sm:text-base md:text-lg lg:text-xl">
            Trinity College London offers two main types of graded music exam — Classical &amp; Jazz and Rock &amp; Pop.
            Both are proper, regulated qualifications. The difference is the style of music and how the exam works.
          </p>
        </div>
      </div>
    </section>

    <!-- CLASSICAL VS ROCK & POP COMPARISON -->
    <section class="bg-brand-bg">
      <div class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)" class="grid grid-cols-1 gap-8 lg:grid-cols-2">
          <!-- Classical & Jazz -->
          <div class="rounded-2xl border border-brand-border bg-brand-surface p-6 shadow-sm">
            <div class="flex items-center gap-3">
              <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-primary/10">
                <Music class="h-5 w-5 text-brand-primary" />
              </div>
              <p class="text-xl font-bold text-brand-primary sm:text-xl">Classical &amp; Jazz</p>
            </div>
            <p class="mt-3 text-base text-brand-text-soft sm:text-base md:text-lg">
              The traditional route. A wide range of instruments, structured technique work, and repertoire spanning centuries of music — from Bach to Brubeck.
            </p>
            <ul class="mt-5 space-y-3">
              <li v-for="item in classicalHighlights" :key="item" class="flex items-start gap-2">
                <CheckCircle class="mt-0.5 h-5 w-5 shrink-0 text-brand-primary" />
                <span class="text-base text-brand-text sm:text-base md:text-lg">{{ item }}</span>
              </li>
            </ul>
          </div>

          <!-- Rock & Pop -->
          <div class="rounded-2xl border border-brand-border bg-brand-surface p-6 shadow-sm">
            <div class="flex items-center gap-3">
              <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-accent/10">
                <Guitar class="h-5 w-5 text-brand-accent" />
              </div>
              <p class="text-xl font-bold text-brand-accent sm:text-xl">Rock &amp; Pop</p>
            </div>
            <p class="mt-3 text-base text-brand-text-soft sm:text-base md:text-lg">
              Play real songs by real artists. Five instruments, a band-focused approach, and music you actually want to listen to — from Arctic Monkeys to Stevie Wonder.
            </p>
            <ul class="mt-5 space-y-3">
              <li v-for="item in rockPopHighlights" :key="item" class="flex items-start gap-2">
                <CheckCircle class="mt-0.5 h-5 w-5 shrink-0 text-brand-accent" />
                <span class="text-base text-brand-text sm:text-base md:text-lg">{{ item }}</span>
              </li>
            </ul>
          </div>
        </div>

        <!-- What they share -->
        <div :class="animClass('fade-up', 2)" class="mt-10">
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            class="md:!text-2xl lg:!text-3xl"
          >
            <template #myTitle>What both have in common</template>
          </MyTextConstructor>
          <div class="mx-auto mt-6 max-w-2xl">
            <ul class="space-y-3">
              <li v-for="item in sharedFeatures" :key="item" class="flex items-start gap-2">
                <CheckCircle class="mt-0.5 h-5 w-5 shrink-0 text-brand-success" />
                <span class="text-base text-brand-text sm:text-base md:text-lg">{{ item }}</span>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </section>

    <!-- EXPLORE MORE GUIDES -->
    <section class="bg-brand-surface">
      <div class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            class="md:!text-2xl lg:!text-3xl"
          >
            <template #myTitle>Explore our exam guides</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-2xl text-center text-base text-brand-text-soft sm:text-base md:text-lg lg:text-xl">
            Everything you need to know, explained clearly — no jargon, no waffle.
          </p>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-8">
          <MyRunnerConstructor
            :theArray="guidePages"
            variant="icon"
            :columns="2"
            spacing="normal"
            maxWidth="4xl"
            @cardClick="handleRunnerClick"
          />
        </div>
      </div>
    </section>

    <!-- FAQ -->
    <section class="bg-brand-bg">
      <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            class="md:!text-2xl lg:!text-3xl"
          >
            <template #myTitle>Common questions</template>
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
            <template #myTitle>Ready to book?</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-xl text-base text-brand-text-soft sm:text-base md:text-lg lg:text-xl">
            Use centre code 120 and your entry gets recognised on our Thank You page, <strong>Hall of Fame</strong> and more.
          </p>
          <a href="/thank-you" class="mt-4 inline-block text-base font-semibold text-brand-accent underline hover:text-brand-primary sm:text-base md:text-lg">
            See the Hall of Fame →
          </a>
          <div class="mt-6">
            <MyButtonConstructor variant="primary" size="large" @click="showBookingModal = true">
              Book Your Exam
            </MyButtonConstructor>
          </div>
        </div>
      </div>
    </section>

    <MyFooter variant="gradient" />

    <BookingModal :show="showBookingModal" @close="showBookingModal = false" />
  </div>
</template>
