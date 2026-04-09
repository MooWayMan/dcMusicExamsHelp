<!-- resources/js/pages/ExamGuideDigital.vue -->
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
import { Monitor, CheckCircle, AlertCircle, Video, Upload, Music, ArrowRight, Clock } from 'lucide-vue-next'

const { animClass } = usePageAnimation()
const showBookingModal = ref(false)

const pageMeta = {
  title: 'Digital Exams Guide — musicExams.help',
  description:
    'How Trinity digital music exams work. Record anywhere, submit online, and receive the same qualification as a face-to-face exam.',
}

const breadcrumbPages = [
  { name: 'Exam Guide', href: '/exam-guide' },
  { name: 'Digital Exams', href: '/exam-guide/digital-exams', current: true },
]

/* ── How it works ── */
const howItWorks = [
  {
    step: '1',
    title: 'Book your digital exam',
    detail: 'Use the <strong>Book Your Exam</strong> button on our site — it guides you to the right Trinity booking system with centre code 120 built in. Choose the digital option when selecting your exam type. Digital exams are available for both Classical & Jazz and Rock & Pop.',
    icon: Music,
  },
  {
    step: '2',
    title: 'Record your performance',
    detail: 'Record anywhere — at home, at school, in a studio, wherever you\'re most comfortable. You can re-record as many times as you like before submitting. Only the final submission is marked.',
    icon: Video,
  },
  {
    step: '3',
    title: 'Upload your video',
    detail: 'Submit your recording through the Trinity upload portal before the deadline. Anyone can submit the recording — the candidate, a teacher, or a parent. The process is straightforward.',
    icon: Upload,
  },
  {
    step: '4',
    title: 'Receive your result',
    detail: 'A qualified Trinity examiner watches your video and marks it using the same criteria as a face-to-face exam. Results are normally provided within 14 days of submission (28 days for diploma exams). The exam report is emailed as an attachment to the email address provided at booking — no portal or login needed. A digital certificate is issued automatically for any Pass, Merit or Distinction. If you want a printed paper certificate posted to you, that\'s an extra £5.',
    icon: Monitor,
  },
]

/* ── What you need ── */
const equipment = [
  'A device that records video and audio — a smartphone, tablet, laptop or camera all work fine',
  'A quiet space with minimal background noise',
  'Good lighting so the examiner can clearly see your hands and instrument',
  'A music stand if you\'re using sheet music',
  'For pianists and keyboard players: make sure the camera angle shows both your hands and the keyboard',
  'For singers: you\'ll need a backing track or accompaniment (recorded or live in the video)',
]

/* ── Digital pathways ── */
const classicalJazzPathways = [
  {
    title: 'Performance pathway',
    description: 'The standard digital option. You record your three pieces (one can be your own composition) plus technical work. The supporting tests (sight-reading, aural etc.) are replaced by Overall Performance Criteria — the examiner assesses your overall musicianship across the whole performance.',
  },
  {
    title: 'Repertoire-only pathway',
    description: 'A simpler option: you record four pieces with no technical work and no supporting tests at all. The examiner assesses everything through your repertoire performances. This is a great option if you want to focus purely on playing pieces.',
  },
]

const rockPopPathway = {
  title: 'Rock & Pop digital format',
  description: 'You perform three songs — two from the Rock & Pop songbook and one Technical Focus song — all played to the official Trinity backing tracks. There are no Session Skills tests in the digital format. Instead, the examiner awards a separate Overall Performance mark (20 out of 100) based on your musicianship across all three songs.',
}

/* ── Digital vs F2F comparison ── */
const comparison = [
  { feature: 'Qualification', digital: 'Same Ofqual-regulated qualification', faceToFace: 'Same Ofqual-regulated qualification' },
  { feature: 'Certificate', digital: 'Digital certificate within 48 hours of result (paper £5 extra)', faceToFace: 'Digital certificate within 48 hours of result (paper £5 extra)' },
  { feature: 'UCAS points', digital: 'Same UCAS points (Grades 6–8)', faceToFace: 'Same UCAS points (Grades 6–8)' },
  { feature: 'Location', digital: 'Record anywhere', faceToFace: 'Attend an exam venue' },
  { feature: 'Nerves', digital: 'Re-record until you\'re happy', faceToFace: 'One live performance' },
  { feature: 'Supporting tests', digital: 'Overall Performance Criteria (or repertoire-only)', faceToFace: 'Sight-reading, aural, etc.' },
  { feature: 'Feedback', digital: 'Written feedback online', faceToFace: 'Written feedback by post and online' },
  { feature: 'Results', digital: 'Normally within 14 days', faceToFace: 'Through centre 120, checked and posted within days' },
  { feature: 'Accompaniment', digital: 'Recorded accompaniment at all grades', faceToFace: 'Live accompanist required from Grade 4' },
  { feature: 'Marking', digital: 'Same marking criteria', faceToFace: 'Same marking criteria' },
  { feature: 'Diplomas', digital: 'ATCL, LTCL and FTCL Recital available digitally', faceToFace: 'ATCL, LTCL and FTCL Recital plus teaching and theory diplomas' },
]

/* ── Tips ── */
const recordingTips = [
  'Do a test recording first to check your sound and picture quality before the real thing',
  'Record in one continuous take — don\'t edit or splice clips together',
  'Make sure your whole body (or at least hands and instrument) is visible throughout',
  'Minimise background noise — close windows, turn off notifications, ask others for quiet',
  'Use natural light or a well-lit room — avoid backlighting (don\'t sit in front of a window)',
  'Place your device on a stable surface or use a tripod — no hand-held filming',
  'Warm up before you hit record, then take a breath and begin when you\'re ready',
  'Top tip: record your performance first, make sure you\'re happy with it, then book and pay. There\'s no rush — don\'t pay until you\'ve got a take you\'re confident with',
]

/* ── What happens after you book (digital) ── */
const afterBookingDigital = [
  {
    step: '1',
    title: 'Confirmation email',
    detail: 'You\'ll receive a booking confirmation straight away with your order reference, candidate details and the amount paid. Check the details carefully — if anything is wrong, contact support@trinitycollege.com immediately.',
  },
  {
    step: '2',
    title: 'Login details arrive within one week',
    detail: 'Within the next week, the candidate will receive an email from noreply@trinitycollege.com with login details for the upload portal. Check your junk/spam folder — add that address as a safe sender. If nothing arrives after a week, contact support@trinitycollege.com.',
  },
  {
    step: '3',
    title: 'Record and submit within four weeks',
    detail: 'Once you have your login details, you have four weeks to record your performance and upload it through the exam portal. You can re-record as many times as you like — only the final submission is marked.',
  },
  {
    step: '4',
    title: 'Results within 14 days',
    detail: 'A qualified Trinity examiner watches your video and marks it. Results are normally provided within 14 days of submission (28 days for diplomas). Your digital certificate is sent within 48 hours of receiving your result.',
  },
]

/* ── FAQ ── */
const faqs = [
  {
    question: 'Can I re-record my performance?',
    answer:
      'Yes — as many times as you like before the submission deadline. Only the final uploaded recording is marked. This is one of the biggest advantages of digital exams.',
  },
  {
    question: 'Who can submit the recording?',
    answer:
      'Anyone — the candidate, a teacher, or a parent. There\'s no restriction on who uploads the video. The important thing is that it\'s submitted before the deadline.',
  },
  {
    question: 'Do I need a professional camera or microphone?',
    answer:
      'No. A modern smartphone records perfectly good quality video and audio. Just make sure the examiner can see your hands and hear you clearly. You don\'t need professional equipment.',
  },
  {
    question: 'Is a digital exam easier than face-to-face?',
    answer:
      'The qualification and marking criteria are identical. Digital exams remove the pressure of a single live performance (you can re-record), but the standard required is the same. Many candidates find digital less stressful, others prefer the energy of performing live.',
  },
  {
    question: 'Can I do Rock & Pop digitally?',
    answer:
      'Yes. Digital exams are available for both Classical & Jazz and Rock & Pop, across all grades. The same digital flexibility applies to both exam types.',
  },
  {
    question: 'What\'s the deadline for uploading?',
    answer:
      'You have 28 days from the date of booking to submit your recording. Check your booking confirmation for the exact deadline. For UCAS purposes, recordings must be uploaded at least three weeks before the second Friday in June.',
  },
  {
    question: 'How long until I get my result?',
    answer:
      'Results for digital graded exams are normally provided within 14 days of submission. Diploma exams may take up to 28 days. Your digital certificate is sent within 48 hours of receiving your result. If you\'d like a printed paper certificate, that\'s an extra £5.',
  },
  {
    question: 'Can I do a diploma digitally?',
    answer:
      'Yes. Trinity now offers digital versions of the ATCL, LTCL and FTCL Recital diplomas for both Classical & Jazz and Rock & Pop. Digital diplomas are marked to the same standard and carry the same qualification. Teaching and theory diplomas are face-to-face only.',
  },
  {
    question: 'Can I use a recorded accompaniment?',
    answer:
      'Yes — recorded accompaniments are permitted at all grades for digital exams. This is a big advantage over face-to-face, where a live accompanist is required from Grade 4 onwards. Trinity publishes its own recorded accompaniments, or you can use any suitable recording.',
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
            <template #myTitle>Digital Exams</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 3)">
          <p class="mx-auto mt-4 max-w-2xl text-center text-base text-brand-text-soft sm:text-base md:text-lg lg:text-xl">
            Record anywhere, submit online, and receive exactly the same qualification as a face-to-face exam.
            Digital exams put you in control of when and where you perform.
          </p>
        </div>
      </div>
    </section>

    <!-- HOW IT WORKS -->
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
            <template #myTitle>How it works</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-8 overflow-hidden rounded-2xl border-4 border-brand-accent bg-white/10 shadow-2xl backdrop-blur-sm">
          <div class="flex items-center justify-center gap-3 bg-black px-5 py-3 sm:px-6">
            <Music class="h-5 w-5 shrink-0 text-brand-accent sm:h-6 sm:w-6" />
            <p class="text-base font-semibold text-white sm:text-lg">Step by step</p>
          </div>
          <div class="space-y-6 p-6">
            <div
              v-for="item in howItWorks"
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

    <!-- WHAT YOU NEED -->
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
            <template #myTitle>What you need</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-8 overflow-hidden rounded-2xl border-4 border-brand-accent bg-white/10 shadow-2xl backdrop-blur-sm">
          <div class="flex items-center justify-center gap-3 bg-black px-5 py-3 sm:px-6">
            <CheckCircle class="h-5 w-5 shrink-0 text-brand-accent sm:h-6 sm:w-6" />
            <p class="text-base font-semibold text-white sm:text-lg">Equipment checklist</p>
          </div>
          <div class="p-6">
            <ul class="space-y-4">
              <li v-for="item in equipment" :key="item" class="flex items-start gap-3">
                <CheckCircle class="mt-0.5 h-5 w-5 shrink-0 text-brand-accent" />
                <span class="text-base text-white sm:text-base md:text-lg">{{ item }}</span>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </section>

    <!-- DIGITAL PATHWAYS -->
    <section
      class="relative"
      style="background-image: url('https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/blue_BG_11.jpg'); background-size: cover; background-position: center;"
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
            <template #myTitle>Digital pathways</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-2xl text-center text-base text-white/80 sm:text-base md:text-lg">
            Classical &amp; Jazz digital exams offer two pathways. Rock &amp; Pop digital exams follow a single format — all three are explained below.
          </p>
        </div>

        <!-- Classical & Jazz pathways -->
        <div :class="animClass('fade-up', 2)" class="mt-8">
          <p class="mb-4 text-base font-semibold text-white sm:text-base md:text-lg">Classical &amp; Jazz</p>
          <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div
              v-for="pathway in classicalJazzPathways"
              :key="pathway.title"
              class="overflow-hidden rounded-2xl border-4 border-brand-accent bg-white/10 shadow-2xl backdrop-blur-sm"
            >
              <div class="bg-black px-5 py-3 sm:px-6">
                <p class="text-base font-semibold text-white sm:text-lg">{{ pathway.title }}</p>
              </div>
              <div class="p-6">
                <p class="text-base text-white/80 sm:text-base md:text-lg">{{ pathway.description }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Rock & Pop pathway -->
        <div :class="animClass('fade-up', 3)" class="mt-6">
          <p class="mb-4 text-base font-semibold text-white sm:text-base md:text-lg">Rock &amp; Pop</p>
          <div class="overflow-hidden rounded-2xl border-4 border-brand-accent bg-white/10 shadow-2xl backdrop-blur-sm">
            <div class="bg-black px-5 py-3 sm:px-6">
              <p class="text-base font-semibold text-white sm:text-lg">{{ rockPopPathway.title }}</p>
            </div>
            <div class="p-6">
              <p class="text-base text-white/80 sm:text-base md:text-lg">{{ rockPopPathway.description }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- DIGITAL VS F2F -->
    <section
      class="relative bg-cover bg-center bg-no-repeat"
      style="background-image: url('https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/blue_BG_9.jpg')"
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
            <template #myTitle>Digital vs face-to-face</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-8 overflow-hidden rounded-2xl border-4 border-brand-accent bg-white/10 shadow-2xl backdrop-blur-sm">
          <div class="overflow-x-auto p-6">
            <table class="w-full text-left">
              <thead>
                <tr class="border-b-2 border-brand-accent">
                  <th class="py-3 pr-4 text-base font-semibold text-white sm:text-base md:text-lg"></th>
                  <th class="py-3 px-4 text-base font-semibold text-white sm:text-base md:text-lg">Digital</th>
                  <th class="py-3 pl-4 text-base font-semibold text-white sm:text-base md:text-lg">Face-to-face</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(row, index) in comparison"
                  :key="row.feature"
                  :class="index % 2 === 0 ? 'bg-white/5' : ''"
                >
                  <td class="py-3 pr-4 text-base font-medium text-white sm:text-base md:text-lg">{{ row.feature }}</td>
                  <td class="py-3 px-4 text-base text-white/80 sm:text-base md:text-lg">{{ row.digital }}</td>
                  <td class="py-3 pl-4 text-base text-white/80 sm:text-base md:text-lg">{{ row.faceToFace }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Same qualification box -->
        <div :class="animClass('fade-up', 3)" class="mt-8 overflow-hidden rounded-2xl border-4 border-brand-accent bg-white/10 shadow-2xl backdrop-blur-sm">
          <div class="flex items-center gap-3 bg-black px-5 py-3 sm:px-6">
            <Monitor class="h-5 w-5 shrink-0 text-brand-accent sm:h-6 sm:w-6" />
            <p class="text-base font-semibold text-white sm:text-lg">Same qualification, different experience</p>
          </div>
          <div class="p-6">
            <p class="text-base text-white/80 sm:text-base md:text-lg">
              The certificate, UCAS points, and recognition are identical whether you take a digital or face-to-face exam. The only difference is how you perform — and being able to re-record takes a lot of the pressure off.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- RECORDING TIPS -->
    <section
      class="relative bg-cover bg-center bg-no-repeat"
      style="background-image: url('https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/blue_BG_6.jpg')"
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
            <template #myTitle>Recording tips</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-8 overflow-hidden rounded-2xl border-4 border-brand-accent bg-white/10 shadow-2xl backdrop-blur-sm">
          <div class="flex items-center justify-center gap-3 bg-black px-5 py-3 sm:px-6">
            <Video class="h-5 w-5 shrink-0 text-brand-accent sm:h-6 sm:w-6" />
            <p class="text-base font-semibold text-white sm:text-lg">Top tips</p>
          </div>
          <div class="p-6">
            <ul class="space-y-4">
              <li v-for="tip in recordingTips" :key="tip" class="flex items-start gap-3">
                <Video class="mt-0.5 h-5 w-5 shrink-0 text-brand-accent" />
                <span class="text-base text-white sm:text-base md:text-lg">{{ tip }}</span>
              </li>
            </ul>
          </div>
        </div>

        <!-- Warning box -->
        <div :class="animClass('fade-up', 3)" class="mt-8 overflow-hidden rounded-2xl border-4 border-brand-accent bg-white/10 shadow-2xl backdrop-blur-sm">
          <div class="flex items-center gap-3 bg-black px-5 py-3 sm:px-6">
            <AlertCircle class="h-5 w-5 shrink-0 text-brand-accent sm:h-6 sm:w-6" />
            <p class="text-base font-semibold text-white sm:text-lg">One continuous take</p>
          </div>
          <div class="p-6">
            <p class="text-base text-white/80 sm:text-base md:text-lg">
              Your recording must be a single, unedited take — no cuts, splices, or effects. Trinity will reject edited recordings. You can re-record the whole thing as many times as you like, but each attempt must be one continuous video.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- WHAT HAPPENS AFTER YOU BOOK -->
    <section
      class="relative bg-cover bg-center bg-no-repeat"
      style="background-image: url('https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/blue_BG_7.jpg')"
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
            Here's what to expect once you've booked a digital exam through Trinity.
          </p>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-8 overflow-hidden rounded-2xl border-4 border-brand-accent bg-white/10 shadow-2xl backdrop-blur-sm">
          <div class="flex items-center justify-center gap-3 bg-black px-5 py-3 sm:px-6">
            <Clock class="h-5 w-5 shrink-0 text-brand-accent sm:h-6 sm:w-6" />
            <p class="text-base font-semibold text-white sm:text-lg">After booking</p>
          </div>
          <div class="space-y-6 p-6">
            <div
              v-for="item in afterBookingDigital"
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
            <p class="text-base font-semibold text-white sm:text-lg">Cancellation</p>
          </div>
          <div class="p-6">
            <p class="text-base text-white/80 sm:text-base md:text-lg">
              You can cancel within 14 days of the day of booking. Trinity will reimburse your payment, minus a portion to cover reasonable costs. After 14 days, cancellation is not guaranteed.
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
            <template #myTitle>Digital exam questions</template>
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
            <template #myTitle>Record your best performance</template>
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
