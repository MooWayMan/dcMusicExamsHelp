<!-- resources/js/pages/ExamGuideDigital.vue -->
<script setup lang="ts">
import { usePageAnimation } from '@/composables/usePageAnimation'
import Head from '@/components/layouts/Head.vue'
import Navbar from '@/components/layouts/Navbar.vue'
import Breadcrumbs from '@/components/layouts/Breadcrumbs.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MyAccordionConstructor from '@/components/reusables/MyAccordionConstructor.vue'
import MyFooter from '@/components/layouts/MyFooter.vue'
import { Monitor, CheckCircle, AlertCircle, Video, Upload, Music, ArrowRight } from 'lucide-vue-next'

const { animClass } = usePageAnimation()

const pageMeta = {
  title: 'Digital Exams Guide — musicExams.help',
  description:
    'How Trinity digital music exams work. Record anywhere, submit online, and receive the same qualification as a face-to-face exam.',
}

const breadcrumbPages = [
  { name: 'Exam Guide', href: '/exam-guide' },
  { name: 'Digital Exams', href: '/exam-guide/digital-exams', current: true },
]

const bookingUrl = 'https://booking.trinitycollege.com/?larCode=120'

/* ── How it works ── */
const howItWorks = [
  {
    step: '1',
    title: 'Book your digital exam',
    detail: 'Book through Trinity\'s online portal using centre code 120. Choose the digital option when selecting your exam type. Digital exams are available for both Classical & Jazz and Rock & Pop.',
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
    detail: 'A qualified Trinity examiner watches your video and marks it using the same criteria as a face-to-face exam. Results and detailed feedback arrive online within a few weeks.',
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

/* ── Digital pathways (Classical & Jazz) ── */
const digitalPathways = [
  {
    title: 'Performance pathway',
    description: 'The standard digital option. You record your three pieces (one can be your own composition) plus technical work. The supporting tests (sight-reading, aural etc.) are replaced by Overall Performance Criteria — the examiner assesses your overall musicianship across the whole performance.',
  },
  {
    title: 'Repertoire-only pathway',
    description: 'A simpler option: you record four pieces with no technical work and no supporting tests at all. The examiner assesses everything through your repertoire performances. This is a great option if you want to focus purely on playing pieces.',
  },
]

/* ── Digital vs F2F comparison ── */
const comparison = [
  { feature: 'Qualification', digital: 'Same Ofqual-regulated qualification', faceToFace: 'Same Ofqual-regulated qualification' },
  { feature: 'Certificate', digital: 'Identical certificate', faceToFace: 'Identical certificate' },
  { feature: 'UCAS points', digital: 'Same UCAS points (Grades 6–8)', faceToFace: 'Same UCAS points (Grades 6–8)' },
  { feature: 'Location', digital: 'Record anywhere', faceToFace: 'Attend an exam venue' },
  { feature: 'Nerves', digital: 'Re-record until you\'re happy', faceToFace: 'One live performance' },
  { feature: 'Supporting tests', digital: 'Overall Performance Criteria (or repertoire-only)', faceToFace: 'Sight-reading, aural, etc.' },
  { feature: 'Feedback', digital: 'Written feedback online', faceToFace: 'Written feedback by post and online' },
  { feature: 'Marking', digital: 'Same marking criteria', faceToFace: 'Same marking criteria' },
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
      'Trinity sets submission windows throughout the year. Check the Trinity website or your exam booking confirmation for the specific deadline. For UCAS purposes, recordings must be uploaded at least three weeks before the second Friday in June.',
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
            <template #myTitle>How it works</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-8 space-y-6">
          <div
            v-for="item in howItWorks"
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
      </div>
    </section>

    <!-- WHAT YOU NEED -->
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
            <template #myTitle>What you need</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-8">
          <ul class="space-y-4">
            <li v-for="item in equipment" :key="item" class="flex items-start gap-3">
              <CheckCircle class="mt-0.5 h-5 w-5 shrink-0 text-brand-success" />
              <span class="text-base text-brand-text sm:text-base md:text-lg">{{ item }}</span>
            </li>
          </ul>
        </div>
      </div>
    </section>

    <!-- DIGITAL PATHWAYS -->
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
            <template #myTitle>Digital pathways</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-2xl text-center text-base text-brand-text-soft sm:text-base md:text-lg">
            Classical &amp; Jazz digital exams offer two pathways. Rock &amp; Pop digital exams follow a single format.
          </p>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2">
          <div
            v-for="pathway in digitalPathways"
            :key="pathway.title"
            class="rounded-2xl border border-brand-border bg-brand-surface p-6 shadow-sm"
          >
            <p class="text-base font-bold text-brand-primary sm:text-base md:text-lg">{{ pathway.title }}</p>
            <p class="mt-2 text-base text-brand-text-soft sm:text-base md:text-lg">{{ pathway.description }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- DIGITAL VS F2F -->
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
            <template #myTitle>Digital vs face-to-face</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-8 overflow-x-auto">
          <table class="w-full text-left">
            <thead>
              <tr class="border-b-2 border-brand-primary">
                <th class="py-3 pr-4 text-base font-semibold text-brand-text sm:text-base md:text-lg"></th>
                <th class="py-3 px-4 text-base font-semibold text-brand-accent sm:text-base md:text-lg">Digital</th>
                <th class="py-3 pl-4 text-base font-semibold text-brand-primary sm:text-base md:text-lg">Face-to-face</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(row, index) in comparison"
                :key="row.feature"
                :class="index % 2 === 0 ? 'bg-brand-surface' : 'bg-brand-surface-soft'"
              >
                <td class="py-3 pr-4 text-base font-medium text-brand-text sm:text-base md:text-lg">{{ row.feature }}</td>
                <td class="py-3 px-4 text-base text-brand-text-soft sm:text-base md:text-lg">{{ row.digital }}</td>
                <td class="py-3 pl-4 text-base text-brand-text-soft sm:text-base md:text-lg">{{ row.faceToFace }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Same qualification box -->
        <div :class="animClass('fade-up', 3)" class="mt-6 rounded-2xl bg-brand-accent/5 p-6 ring-1 ring-brand-accent/20">
          <div class="flex items-start gap-3">
            <Monitor class="mt-0.5 h-6 w-6 shrink-0 text-brand-accent" />
            <div>
              <p class="text-base font-semibold text-brand-text sm:text-base md:text-lg">Same qualification, different experience</p>
              <p class="mt-1 text-base text-brand-text-soft sm:text-base md:text-lg">
                The certificate, UCAS points, and recognition are identical whether you take a digital or face-to-face exam. The only difference is how you perform — and being able to re-record takes a lot of the pressure off.
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- RECORDING TIPS -->
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
            <template #myTitle>Recording tips</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-8">
          <ul class="space-y-4">
            <li v-for="tip in recordingTips" :key="tip" class="flex items-start gap-3">
              <Video class="mt-0.5 h-5 w-5 shrink-0 text-brand-primary" />
              <span class="text-base text-brand-text sm:text-base md:text-lg">{{ tip }}</span>
            </li>
          </ul>
        </div>

        <!-- Warning box -->
        <div :class="animClass('fade-up', 3)" class="mt-8 rounded-2xl bg-brand-danger-soft p-6 ring-1 ring-brand-danger/20">
          <div class="flex items-start gap-3">
            <AlertCircle class="mt-0.5 h-6 w-6 shrink-0 text-brand-danger" />
            <div>
              <p class="text-base font-semibold text-brand-text sm:text-base md:text-lg">One continuous take</p>
              <p class="mt-1 text-base text-brand-text-soft sm:text-base md:text-lg">
                Your recording must be a single, unedited take — no cuts, splices, or effects. Trinity will reject edited recordings. You can re-record the whole thing as many times as you like, but each attempt must be one continuous video.
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
            <template #myTitle>Record your best performance</template>
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
