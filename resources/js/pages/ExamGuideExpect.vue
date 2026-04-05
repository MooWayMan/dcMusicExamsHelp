<!-- resources/js/pages/ExamGuideExpect.vue -->
<script setup lang="ts">
import { usePageAnimation } from '@/composables/usePageAnimation'
import Head from '@/components/layouts/Head.vue'
import Navbar from '@/components/layouts/Navbar.vue'
import Breadcrumbs from '@/components/layouts/Breadcrumbs.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MyAccordionConstructor from '@/components/reusables/MyAccordionConstructor.vue'
import MyFooter from '@/components/layouts/MyFooter.vue'
import { Clock, CheckCircle, AlertCircle, Music, ArrowRight } from 'lucide-vue-next'

const { animClass } = usePageAnimation()

const pageMeta = {
  title: 'What to Expect on Exam Day — musicExams.help',
  description:
    'A friendly, step-by-step guide to what happens on Trinity music exam day. What to bring, what happens in the room, and tips for staying calm.',
}

const breadcrumbPages = [
  { name: 'Exam Guide', href: '/exam-guide' },
  { name: 'What to Expect', href: '/exam-guide/what-to-expect', current: true },
]

const bookingUrl = 'https://booking.trinitycollege.com/?larCode=120'

/* ── Before the day ── */
const beforeTheDay = [
  'Practise your pieces until they feel comfortable — not just note-perfect, but musically confident',
  'Check your exam date, time and venue well in advance',
  'Make sure you have the correct sheet music — original copies, not photocopies (the examiner needs to see them)',
  'Prepare any scales, arpeggios or technical work required for your grade',
  'If you play an instrument that needs accompaniment, arrange your accompanist early',
  'Get a good night\'s sleep — it makes more difference than a last-minute practice session',
]

/* ── What to bring ── */
const whatToBring = [
  'Your instrument (tuned and in good working order)',
  'Your sheet music — original copies for the examiner to follow',
  'Any backing tracks if required (Rock & Pop candidates)',
  'A bottle of water',
  'Your exam confirmation or appointment details',
  'A positive attitude — the examiner wants you to do well!',
]

/* ── In the room (F2F) ── */
const inTheRoom = [
  {
    step: '1',
    title: 'You\'ll be welcomed in',
    detail: 'The examiner will greet you and help you feel at ease. They\'ll check your name and the exam you\'re taking. You can take a moment to settle in.',
  },
  {
    step: '2',
    title: 'Pieces first',
    detail: 'You\'ll play your prepared pieces. For Classical & Jazz, that\'s three pieces from the repertoire list — and one can be your own composition if you like. For Rock & Pop, you play three songs from the book (one must be a Technical Focus song), with the option to replace your third song with an own choice. You choose the order.',
  },
  {
    step: '3',
    title: 'Technical work',
    detail: 'For Classical & Jazz, the examiner will ask for scales, arpeggios and exercises from the syllabus. Rock & Pop candidates play their Technical Focus song as one of their four pieces.',
  },
  {
    step: '4',
    title: 'Supporting tests',
    detail: 'Classical & Jazz exams include supporting tests — sight-reading, aural, improvisation or musical knowledge (depending on your grade and choices). Rock & Pop has Session Skills — either Improvising or Playback.',
  },
  {
    step: '5',
    title: 'That\'s it!',
    detail: 'The examiner will thank you and you\'re done. The whole thing typically takes 12–30 minutes depending on the grade. Results arrive by post and online within a few weeks.',
  },
]

/* ── Digital exam steps ── */
const digitalSteps = [
  {
    step: '1',
    title: 'Record anywhere',
    detail: 'Record your performance at home, at school, in a studio — wherever you feel most comfortable. You can re-record until you\'re happy.',
  },
  {
    step: '2',
    title: 'Upload your video',
    detail: 'Submit your recording through the Trinity upload portal. Anyone can submit — the candidate, a teacher, or a parent.',
  },
  {
    step: '3',
    title: 'An examiner watches',
    detail: 'A qualified Trinity examiner watches your video and marks it using the same criteria as a face-to-face exam.',
  },
  {
    step: '4',
    title: 'Results arrive',
    detail: 'You\'ll receive your result and feedback online. The certificate is the same as face-to-face — no difference at all.',
  },
]

/* ── Tips for nerves ── */
const nerveTips = [
  'Remember: the examiner is on your side. They want you to play your best.',
  'Take a breath before you start each piece. There\'s no rush.',
  'If you make a mistake, keep going. Examiners mark the whole performance, not individual notes.',
  'Play through your pieces in front of family or friends beforehand — performing for any audience builds confidence.',
  'Arrive early so you\'re not rushing. Give yourself time to warm up.',
  'Focus on the music, not the marks. If you enjoy playing, it comes through in your performance.',
]

/* ── FAQ ── */
const faqs = [
  {
    question: 'How long does the exam last?',
    answer:
      'It depends on the grade. Initial and Grade 1 exams are around 12 minutes. By Grade 8, you\'re looking at around 25–30 minutes. Your appointment time includes a few minutes for settling in.',
  },
  {
    question: 'Can a parent or teacher come into the room?',
    answer:
      'For younger candidates (under 16), a parent or guardian can sit quietly in the room if the candidate wants. Teachers can\'t be in the room during the exam itself, but they can wait nearby.',
  },
  {
    question: 'What happens if I make a mistake?',
    answer:
      'Keep playing! Examiners are assessing your overall musicianship — tone, expression, rhythm, and how you handle the piece as a whole. A small slip won\'t ruin your mark. Stopping and starting again is more noticeable than carrying on.',
  },
  {
    question: 'Do I need to memorise my pieces?',
    answer:
      'No — you can use your sheet music. However, for Rock & Pop exams, performing from memory is encouraged (and more natural for those styles). Classical & Jazz candidates can have music on the stand.',
  },
  {
    question: 'What if I\'m ill on exam day?',
    answer:
      'Contact Trinity as soon as possible. They have procedures for rescheduling in cases of illness. Don\'t try to push through a serious illness — it\'s better to rearrange and perform at your best.',
  },
]
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
            <template #myTitle>What to Expect</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 3)">
          <p class="mx-auto mt-4 max-w-2xl text-center text-base text-brand-text-soft sm:text-base md:text-lg lg:text-xl">
            Whether it's your first exam or your eighth, knowing what to expect takes the mystery out of exam day.
            Here's a friendly walkthrough so there are no surprises.
          </p>
        </div>
      </div>
    </section>

    <!-- BEFORE THE DAY -->
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
            <template #myTitle>Before the day</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-8">
          <ul class="space-y-4">
            <li v-for="item in beforeTheDay" :key="item" class="flex items-start gap-3">
              <CheckCircle class="mt-0.5 h-5 w-5 shrink-0 text-brand-success" />
              <span class="text-base text-brand-text sm:text-base md:text-lg">{{ item }}</span>
            </li>
          </ul>
        </div>
      </div>
    </section>

    <!-- WHAT TO BRING -->
    <section class="bg-brand-surface">
      <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            class="md:!text-2xl lg:!text-3xl"
          >
            <template #myTitle>What to bring</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-8">
          <ul class="space-y-4">
            <li v-for="item in whatToBring" :key="item" class="flex items-start gap-3">
              <CheckCircle class="mt-0.5 h-5 w-5 shrink-0 text-brand-accent" />
              <span class="text-base text-brand-text sm:text-base md:text-lg">{{ item }}</span>
            </li>
          </ul>
        </div>
      </div>
    </section>

    <!-- IN THE ROOM (F2F) -->
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
            <template #myTitle>In the exam room</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-2xl text-center text-base text-brand-text-soft sm:text-base md:text-lg">
            Here's what happens step by step in a face-to-face exam.
          </p>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-8 space-y-6">
          <div
            v-for="item in inTheRoom"
            :key="item.step"
            class="flex gap-4"
          >
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-primary text-lg font-bold text-brand-text-inverse">
              {{ item.step }}
            </div>
            <div>
              <p class="text-base font-semibold text-brand-text sm:text-base md:text-lg">{{ item.title }}</p>
              <p class="mt-1 text-base text-brand-text-soft sm:text-base md:text-lg">{{ item.detail }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- DIGITAL EXAM STEPS -->
    <section class="bg-brand-surface">
      <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            class="md:!text-2xl lg:!text-3xl"
          >
            <template #myTitle>Taking a digital exam?</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-2xl text-center text-base text-brand-text-soft sm:text-base md:text-lg">
            Digital exams follow a different process — but the qualification is exactly the same.
          </p>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-8 space-y-6">
          <div
            v-for="item in digitalSteps"
            :key="item.step"
            class="flex gap-4"
          >
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-accent text-lg font-bold text-brand-text-inverse">
              {{ item.step }}
            </div>
            <div>
              <p class="text-base font-semibold text-brand-text sm:text-base md:text-lg">{{ item.title }}</p>
              <p class="mt-1 text-base text-brand-text-soft sm:text-base md:text-lg">{{ item.detail }}</p>
            </div>
          </div>
        </div>

        <div :class="animClass('fade-up', 3)" class="mt-6 text-center">
          <a href="/exam-guide/digital-exams">
            <MyButtonConstructor variant="outline" size="medium">
              Full Digital Exams Guide
            </MyButtonConstructor>
          </a>
        </div>
      </div>
    </section>

    <!-- TIPS FOR NERVES -->
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
            <template #myTitle>Keeping nerves in check</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-8">
          <ul class="space-y-4">
            <li v-for="tip in nerveTips" :key="tip" class="flex items-start gap-3">
              <Music class="mt-0.5 h-5 w-5 shrink-0 text-brand-primary" />
              <span class="text-base text-brand-text sm:text-base md:text-lg">{{ tip }}</span>
            </li>
          </ul>
        </div>

        <!-- Reassurance box -->
        <div :class="animClass('fade-up', 3)" class="mt-8 rounded-2xl bg-brand-accent/5 p-6 ring-1 ring-brand-accent/20">
          <div class="flex items-start gap-3">
            <Clock class="mt-0.5 h-6 w-6 shrink-0 text-brand-accent" />
            <div>
              <p class="text-base font-semibold text-brand-text sm:text-base md:text-lg">It's shorter than you think</p>
              <p class="mt-1 text-base text-brand-text-soft sm:text-base md:text-lg">
                Most exams are over in 15–20 minutes. By the time you've played your first piece and settled in, you're already halfway through. It goes quickly!
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- FAQ -->
    <section class="bg-brand-surface">
      <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            class="md:!text-2xl lg:!text-3xl"
          >
            <template #myTitle>Exam day questions</template>
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
    <section class="bg-brand-bg">
      <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)" class="text-center">
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            class="md:!text-2xl lg:!text-3xl"
          >
            <template #myTitle>Ready to take the next step?</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-xl text-base text-brand-text-soft sm:text-base md:text-lg lg:text-xl">
            Book through centre 120 and your achievement gets celebrated here too — Hall of Fame, certificates and recognition.
          </p>
          <div class="mt-6 flex flex-wrap items-center justify-center gap-4">
            <a :href="bookingUrl" target="_blank" rel="noopener noreferrer">
              <MyButtonConstructor variant="primary" size="large">
                Book Your Exam
              </MyButtonConstructor>
            </a>
            <a href="/exam-guide">
              <MyButtonConstructor variant="outline" size="large">
                Back to Exam Guide
              </MyButtonConstructor>
            </a>
          </div>
        </div>
      </div>
    </section>

    <MyFooter variant="gradient" />
  </div>
</template>
