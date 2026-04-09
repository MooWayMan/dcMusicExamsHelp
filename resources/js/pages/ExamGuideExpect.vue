<!-- resources/js/pages/ExamGuideExpect.vue -->
<script setup lang="ts">
import { ref } from 'vue'
import { usePageAnimation } from '@/composables/usePageAnimation'
import Head from '@/components/layouts/Head.vue'
import Navbar from '@/components/layouts/Navbar.vue'
import Breadcrumbs from '@/components/layouts/Breadcrumbs.vue'
import BookingModal from '@/components/BookingModal.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MyAccordionConstructor from '@/components/reusables/MyAccordionConstructor.vue'
import MyFooter from '@/components/layouts/MyFooter.vue'
import { Clock, CheckCircle, AlertCircle, Music, ArrowRight } from 'lucide-vue-next'

const { animClass } = usePageAnimation()
const showBookingModal = ref(false)

const pageMeta = {
  title: 'What to Expect on Exam Day — musicExams.help',
  description:
    'A friendly, step-by-step guide to what happens on Trinity music exam day. What to bring, what happens in the room, and tips for staying calm.',
}

const breadcrumbPages = [
  { name: 'Exam Guide', href: '/exam-guide' },
  { name: 'What to Expect', href: '/exam-guide/what-to-expect', current: true },
]

/* ── Before the day ── */
const beforeTheDay = [
  'Practise your pieces until they feel comfortable — not just note-perfect, but musically confident',
  'Check your exam date, time and venue well in advance',
  'Make sure you have the correct sheet music — original copies, not photocopies (Trinity requires this for copyright reasons). If any pieces aren\'t from a Trinity publication, prepare an extra copy for the examiner',
  'Prepare any scales, arpeggios or technical work required for your grade',
  'If you play an instrument that needs accompaniment, arrange your accompanist early',
  'Get a good night\'s sleep — it makes more difference than a last-minute practice session',
]

/* ── What to bring ── */
const whatToBring = [
  'Your instrument (tuned and in good working order)',
  'Your sheet music — original copies, not photocopies (copyright rules). If your piece isn\'t published by Trinity, you\'ll also need to provide a copy for the examiner to follow along with. A tablet or iPad with photos of the music works well — just make sure it\'s in aeroplane mode',
  'Any backing tracks if required — at our Liverpool and Wirral centres, playback equipment and all cables and adapters are provided. Just have your backing track ready on your phone or device',
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
    title: 'You choose the order',
    detail: 'You can present the sections of your exam in any order — just write your preferred order on the appointment slip. If you don\'t indicate a preference, the examiner will follow the default order (which varies by instrument). For Classical & Jazz, the three sections are pieces, technical work and supporting tests. For Rock & Pop, it\'s three songs and Session Skills.',
  },
  {
    step: '3',
    title: 'Pieces or songs',
    detail: 'For Classical & Jazz, you play three pieces from the repertoire list — one can be your own composition if you like. For Rock & Pop, you play three songs from the book (one must be a Technical Focus song), with the option to replace the second song with an own choice or your own composition.',
  },
  {
    step: '4',
    title: 'Technical work and supporting tests',
    detail: 'Classical & Jazz exams include scales, arpeggios and exercises, plus supporting tests — sight-reading, aural, improvisation or musical knowledge (depending on your grade and choices). Rock & Pop has Session Skills instead — either Improvising or Playback.',
  },
  {
    step: '5',
    title: 'That\'s it!',
    detail: 'The examiner will thank you and you\'re done. The whole thing typically takes 12–30 minutes depending on the grade. There are no results on the day — see the results section below for what happens next.',
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

/* ── What happens after you book (F2F) ── */
const afterBookingF2F = [
  {
    step: '1',
    title: 'Booking confirmation',
    detail: 'You\'ll receive a confirmation email with your order reference, candidate name, instrument, grade and the amount paid. Check the details carefully — if anything is wrong, contact <a href="mailto:support@trinitycollege.com" class="font-semibold text-white underline hover:text-white/70" target="_blank">support@trinitycollege.com</a> straight away.',
  },
  {
    step: '2',
    title: 'Exam scheduled email',
    detail: 'Closer to the exam session, you\'ll receive an email from noreply@trinitycollege.com with your exam date, time and venue address. This email also includes an appointment slip. Check your junk/spam folder — add the address as a safe sender.',
  },
  {
    step: '3',
    title: 'Print the appointment slip',
    detail: 'The appointment slip must be printed and filled in with your choices (pieces, scales, supporting tests). It must be handed to the examiner when you walk into the room. Without it, the examiner won\'t know what you\'re performing.',
  },
  {
    step: '4',
    title: 'Exam day',
    detail: 'Arrive at the venue in good time. Bring your instrument, sheet music, appointment slip and any accompanist if needed. The exam typically lasts 12–30 minutes depending on the grade.',
  },
  {
    step: '5',
    title: 'Results and certificate',
    detail: '<strong class="text-white">Important: there are no results on the day.</strong> The process differs depending on the type of exam you took:<br /><br /><span class="inline-block rounded-full bg-brand-accent/20 px-3 py-0.5 -ml-3 text-sm font-bold text-white">Classical &amp; Jazz face-to-face</span> The examiner completes a detailed written report form for each candidate. These are collected by your centre representative, who checks the marks, scans each report and posts the original to you by second-class post. This means you typically receive your written report within days of the exam — much faster than the standard turnaround. The examiner separately enters the results onto Trinity\'s system, which can take longer. Once validated, Trinity sends an email with a link to download your official digital certificate (free, included). From that same link you can order a printed paper certificate for £5 if you\'d like one.<br /><br /><span class="inline-block rounded-full bg-brand-accent/20 px-3 py-0.5 -ml-3 text-sm font-bold text-white">Rock &amp; Pop face-to-face</span> The same process applies — your centre representative checks, scans and posts the examiner\'s written report to you. Results also appear on your MyTrinity account online. Your official digital certificate is issued by Trinity once the result is validated.<br /><br /><span class="inline-block rounded-full bg-brand-accent/20 px-3 py-0.5 -ml-3 text-sm font-bold text-white">Digital exams</span> Results and feedback are sent to you by email directly from Trinity. There is no paper report — everything is online. Your official digital certificate is issued once the result is validated.<br /><br />In all cases, results are provisional until validated by Trinity. Occasionally results may be delayed — for example if an exam is referred for further review. If you have any concerns about a delayed result, contact <a href="mailto:support@trinitycollege.com" class="font-semibold text-white underline hover:text-white/70" target="_blank">support@trinitycollege.com</a>.',
  },
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
      'No — parents and teachers are not allowed in the exam room during a face-to-face exam. The only exception is if a very young child needs help setting up equipment (e.g. an electric keyboard) — a parent can come in to help with setup but must then leave and go to the designated waiting area. For digital exams, you may need to help with the recording setup, but the performance itself is the candidate\'s.',
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
            <template #myTitle>What to Expect</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 3)">
          <p class="mx-auto mt-4 max-w-2xl text-center text-base text-brand-text-soft sm:text-base md:text-lg lg:text-xl">
            This guide covers what happens at a face-to-face exam. Whether it's your first or your eighth,
            knowing what to expect takes the mystery out of exam day. Taking a digital exam instead?
            <a href="/exam-guide/digital-exams" class="font-semibold text-brand-accent hover:underline">See our digital exams guide</a>.
          </p>
        </div>
      </div>
    </section>

    <!-- BEFORE THE DAY & WHAT TO BRING -->
    <section
      class="relative bg-cover bg-center bg-no-repeat"
      style="background-image: url('https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/blue_BG_9.jpg')"
    >
      <div class="absolute inset-0 bg-black/50"></div>
      <div class="relative mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)" class="overflow-hidden rounded-2xl border-4 border-brand-accent bg-white/10 shadow-2xl backdrop-blur-sm">
          <div class="flex items-center justify-center gap-3 bg-black px-5 py-3 sm:px-6">
            <CheckCircle class="h-5 w-5 shrink-0 text-brand-accent sm:h-6 sm:w-6" />
            <p class="text-base font-semibold text-white sm:text-lg">Before the day</p>
          </div>
          <div class="p-6">
            <ul class="space-y-4">
              <li v-for="item in beforeTheDay" :key="item" class="flex items-start gap-3">
                <CheckCircle class="mt-0.5 h-5 w-5 shrink-0 text-brand-accent" />
                <span class="text-base text-white sm:text-base md:text-lg">{{ item }}</span>
              </li>
            </ul>
          </div>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-8 overflow-hidden rounded-2xl border-4 border-brand-accent bg-white/10 shadow-2xl backdrop-blur-sm">
          <div class="flex items-center justify-center gap-3 bg-black px-5 py-3 sm:px-6">
            <CheckCircle class="h-5 w-5 shrink-0 text-brand-accent sm:h-6 sm:w-6" />
            <p class="text-base font-semibold text-white sm:text-lg">What to bring</p>
          </div>
          <div class="p-6">
            <ul class="space-y-4">
              <li v-for="item in whatToBring" :key="item" class="flex items-start gap-3">
                <CheckCircle class="mt-0.5 h-5 w-5 shrink-0 text-brand-accent" />
                <span class="text-base text-white sm:text-base md:text-lg">{{ item }}</span>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </section>

    <!-- IN THE ROOM (F2F) -->
    <section
      class="relative"
      style="background-image: url('https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/blue_BG_7.jpg'); background-size: cover; background-position: center;"
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
            <template #myTitle>In the exam room — face-to-face</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-2xl text-center text-base text-white/80 sm:text-base md:text-lg">
            Here's what happens step by step when you walk in.
          </p>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-8 overflow-hidden rounded-2xl border-4 border-brand-accent bg-white/10 shadow-2xl backdrop-blur-sm">
          <div class="flex items-center justify-center gap-3 bg-black px-5 py-3 sm:px-6">
            <Music class="h-5 w-5 shrink-0 text-brand-accent sm:h-6 sm:w-6" />
            <p class="text-base font-semibold text-white sm:text-lg">Step by step</p>
          </div>
          <div class="space-y-6 p-6">
            <div
              v-for="item in inTheRoom"
              :key="item.step"
              class="flex gap-4"
            >
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-brand-primary via-brand-accent to-brand-primary text-lg font-bold text-white shadow-md sm:h-12 sm:w-12 sm:text-xl">
                {{ item.step }}
              </div>
              <div>
                <p class="text-base font-semibold text-white sm:text-base md:text-lg">{{ item.title }}</p>
                <p class="mt-1 text-base text-white/80 sm:text-base md:text-lg">{{ item.detail }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- DIGITAL EXAM STEPS -->
    <section
      class="relative bg-cover bg-center bg-no-repeat"
      style="background-image: url('https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/blue_BG_11.jpg')"
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
            <template #myTitle>Taking a digital exam?</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-2xl text-center text-base text-white/80 sm:text-base md:text-lg">
            Digital exams follow a different process — but the qualification is exactly the same.
          </p>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-8 overflow-hidden rounded-2xl border-4 border-brand-accent bg-white/10 shadow-2xl backdrop-blur-sm">
          <div class="flex items-center justify-center gap-3 bg-black px-5 py-3 sm:px-6">
            <Music class="h-5 w-5 shrink-0 text-brand-accent sm:h-6 sm:w-6" />
            <p class="text-base font-semibold text-white sm:text-lg">How it works</p>
          </div>
          <div class="space-y-6 p-6">
            <div
              v-for="item in digitalSteps"
              :key="item.step"
              class="flex gap-4"
            >
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-brand-primary via-brand-accent to-brand-primary text-lg font-bold text-white shadow-md sm:h-12 sm:w-12 sm:text-xl">
                {{ item.step }}
              </div>
              <div>
                <p class="text-base font-semibold text-white sm:text-base md:text-lg">{{ item.title }}</p>
                <p class="mt-1 text-base text-white/80 sm:text-base md:text-lg">{{ item.detail }}</p>
              </div>
            </div>
          </div>
        </div>

        <div :class="animClass('fade-up', 3)" class="mt-6 text-center">
          <a href="/exam-guide/digital-exams">
            <MyButtonConstructor variant="light" size="medium">
              Full Digital Exams Guide
            </MyButtonConstructor>
          </a>
        </div>
      </div>
    </section>

    <!-- TIPS FOR NERVES -->
    <section
      class="relative bg-cover bg-center bg-no-repeat"
      style="background-image: url('https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/blue_BG_10.jpg')"
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
            <template #myTitle>Keeping nerves in check</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-8 overflow-hidden rounded-2xl border-4 border-brand-accent bg-white/10 shadow-2xl backdrop-blur-sm">
          <div class="flex items-center justify-center gap-3 bg-black px-5 py-3 sm:px-6">
            <Music class="h-5 w-5 shrink-0 text-brand-accent sm:h-6 sm:w-6" />
            <p class="text-base font-semibold text-white sm:text-lg">Top tips</p>
          </div>
          <div class="p-6">
            <ul class="space-y-4">
              <li v-for="tip in nerveTips" :key="tip" class="flex items-start gap-3">
                <Music class="mt-0.5 h-5 w-5 shrink-0 text-brand-accent" />
                <span class="text-base text-white sm:text-base md:text-lg">{{ tip }}</span>
              </li>
            </ul>
          </div>
        </div>

        <!-- Reassurance box -->
        <div :class="animClass('fade-up', 3)" class="mt-8 overflow-hidden rounded-2xl border-4 border-brand-accent bg-white/10 shadow-2xl backdrop-blur-sm">
          <div class="flex items-center gap-3 bg-black px-5 py-3 sm:px-6">
            <Clock class="h-5 w-5 shrink-0 text-brand-accent sm:h-6 sm:w-6" />
            <p class="text-base font-semibold text-white sm:text-lg">It's shorter than you think</p>
          </div>
          <div class="p-6">
            <p class="text-base text-white/80 sm:text-base md:text-lg">
              Most exams are over in 15–20 minutes. By the time you've played your first piece and settled in, you're already halfway through. It goes quickly!
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- WHAT HAPPENS AFTER YOU BOOK -->
    <section
      class="relative bg-cover bg-center bg-no-repeat"
      style="background-image: url('https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/blue_BG_5.jpg')"
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
            <template #myTitle>What happens after you book?</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-2xl text-center text-base text-white/80 sm:text-base md:text-lg">
            Here's what to expect once you've booked a face-to-face exam.
          </p>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-8 overflow-hidden rounded-2xl border-4 border-brand-accent bg-white/10 shadow-2xl backdrop-blur-sm">
          <div class="flex items-center justify-center gap-3 bg-black px-5 py-3 sm:px-6">
            <Clock class="h-5 w-5 shrink-0 text-brand-accent sm:h-6 sm:w-6" />
            <p class="text-base font-semibold text-white sm:text-lg">After booking</p>
          </div>
          <div class="space-y-6 p-6">
            <div
              v-for="item in afterBookingF2F"
              :key="item.step"
              class="flex gap-4 sm:gap-5"
            >
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-brand-primary via-brand-accent to-brand-primary text-sm font-bold text-white shadow-md sm:h-12 sm:w-12 sm:text-base">
                {{ item.step }}
              </div>
              <div>
                <p class="text-base font-semibold text-white sm:text-lg">{{ item.title }}</p>
                <p class="mt-1 text-sm leading-relaxed text-white/80 sm:text-base">{{ item.detail }}</p>
              </div>
            </div>
          </div>
        </div>

        <div :class="animClass('fade-up', 3)" class="mt-8 overflow-hidden rounded-2xl border-4 border-brand-accent bg-white/10 shadow-2xl backdrop-blur-sm">
          <div class="flex items-center gap-3 bg-black px-5 py-3 sm:px-6">
            <AlertCircle class="h-5 w-5 shrink-0 text-brand-accent sm:h-6 sm:w-6" />
            <p class="text-base font-semibold text-white sm:text-lg">Special needs provisions</p>
          </div>
          <div class="p-6">
            <p class="text-base leading-snug text-white/80 sm:text-base md:text-lg">
              If a candidate has a specific educational need or disability, Trinity offers access arrangements to support them. Visit <a href="https://www.trinitycollege.com/qualifications/music/special-needs" target="_blank" rel="noopener noreferrer" class="font-semibold text-brand-accent underline hover:text-white">Trinity's special needs page</a> for full details and to download the application form. A new form must be submitted for every exam, at least 28 days before the exam date.
            </p>
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
            <template #myTitle>Ready to take the next step?</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-xl text-base text-brand-text-soft sm:text-base md:text-lg lg:text-xl">
            Book through centre 120 and every entry earns at least a <strong>Bravo Certificate</strong> — plus the <strong>Hall of Fame</strong> and more for Merit and Distinction.
          </p>
          <a href="/recognition" class="mt-4 inline-block text-base font-semibold text-brand-accent underline hover:text-brand-primary sm:text-base md:text-lg">
            See the Hall of Fame →
          </a>
          <div class="mt-6 flex flex-wrap items-center justify-center gap-4">
            <MyButtonConstructor variant="primary" size="large" @click="showBookingModal = true">
              Book Your Exam
            </MyButtonConstructor>
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

    <BookingModal :show="showBookingModal" @close="showBookingModal = false" />
  </div>
</template>
