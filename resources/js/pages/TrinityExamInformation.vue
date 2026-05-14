<!-- resources/js/pages/TrinityExamInformation.vue -->
<script setup lang="ts">
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import { usePageAnimation } from '@/composables/usePageAnimation'
import { useBookingModal } from '@/composables/useBookingModal'
import Head from '@/components/layouts/Head.vue'
import Navbar from '@/components/layouts/Navbar.vue'
import Breadcrumbs from '@/components/layouts/Breadcrumbs.vue'
import BookingModal from '@/components/BookingModal.vue'
import LeadMagnetCapture from '@/components/LeadMagnetCapture.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MyAccordionConstructor from '@/components/reusables/MyAccordionConstructor.vue'
import MyFooter from '@/components/layouts/MyFooter.vue'
import { CheckCircle, BookOpen, GraduationCap, ClipboardCheck, Monitor, Receipt, HelpCircle, ArrowRight, ChevronDown } from 'lucide-vue-next'

const { animClass } = usePageAnimation()
const { showBookingModal } = useBookingModal()

const pageMeta = {
  title: 'Booking a Trinity Music Exam — Centre 120 | musicExams.help',
  description:
    'How to book a Trinity College London music exam through Centre 120. Same exam, same fee — just enter 120 at booking. A registered Trinity exam centre.',
}

const breadcrumbPages = [
  { name: 'Trinity Exam Information', href: '/trinity-exam-information', current: true },
]

// Opener — "What do you need help with?" self-selection at the very top
// of the page. Card 1 (first-timers) just scrolls down — the whole page
// is built for them. Cards 2 and 3 expand to a sub-choice and route on.
interface HelpCard {
  id: string
  label: string
  type: 'scroll' | 'choice'
  target?: string
  prompt?: string
  options?: { label: string; href: string }[]
}

const helpCards: HelpCard[] = [
  { id: 'first-time', label: 'This is my first Trinity exam booking', type: 'scroll', target: '#get-started' },
  {
    id: 'checking',
    label: "I've booked before — just checking a few things",
    type: 'choice',
    prompt: 'Which are you?',
    options: [
      { label: 'A teacher', href: '/switch-to-centre-120?from=trinity-exam-information' },
      { label: 'A parent', href: '/for-parents?from=trinity-exam-information' },
      { label: 'Booking for myself', href: '/exam-guide?from=trinity-exam-information' },
    ],
  },
  {
    id: 'goodies',
    label: "I've heard you offer extra recognition and goodies",
    type: 'choice',
    prompt: 'Parent or teacher?',
    options: [
      { label: "I'm a parent", href: '/recognition?from=trinity-exam-information' },
      { label: "I'm a teacher", href: '/for-teachers/awards?from=trinity-exam-information' },
    ],
  },
]

const openHelpCard = ref<string | null>(null)
const toggleHelpCard = (id: string) => {
  openHelpCard.value = openHelpCard.value === id ? null : id
}

// How it works — three plain steps. Deliberately factual, no hard sell:
// search traffic already wants to book, they just need the mechanics.
// Voice is "you" (the person booking) — works for a parent, an adult
// self-applicant, or an independent student alike.
const steps = [
  {
    step: 1,
    title: 'Start your booking',
    detail:
      'Tap the <strong>Book a Trinity Exam</strong> button on this page — it takes you straight to Trinity\'s booking system, where you choose the subject, the grade, who\'s sitting the exam, and a date. Not sure about any of it? That\'s exactly what we\'re here for — just ask.',
  },
  {
    step: 2,
    title: 'Check 120 is in the referral code box',
    detail:
      'For digital exams, we pre-fill <strong>120</strong> in the "referral code" box for you — just check it\'s still there before you pay (a page refresh can clear it). Booking face-to-face at our Liverpool or Wirral venues? You\'re linked to us automatically — no code needed.',
  },
  {
    step: 3,
    title: "That's it — you're booked with us",
    detail:
      'Nothing else for you to do. Whoever\'s sitting the exam is now booked through Centre 120 and part of our free recognition programme: every candidate gets an extra certificate from us — on top of their official Trinity one, whatever the result — plus a place on the Recognition page and entry into the quarterly prize draw. All at no extra cost.',
  },
]

// Expandable detail — the "open it up if you want it" depth. Framed as
// information sections rather than a tacked-on FAQ; the full FAQ still
// lives at /faq and is linked beneath.
const detailItems = [
  {
    id: 'how-centre-works',
    question: 'How does Centre 120 work with Trinity?',
    answer:
      'Centre 120 is a Trinity College London Registered Public Exam Centre — an official route for booking Trinity exams. The exam, the syllabus and the certificate are all Trinity\'s own. We\'re the local centre in Liverpool and the Wirral that handles your booking and looks after you through it.',
  },
  {
    id: 'cost-extra',
    question: 'Does using Centre 120 cost anything extra?',
    answer:
      'No — the Trinity fee is exactly the same. The difference is what comes with it: when you book with code <strong>120</strong>, we\'re here to give you extra help and guidance, a few goodies along the way, and recognition for whoever\'s sitting the exam — we celebrate and appreciate every student who books with us.',
  },
  {
    id: 'how-to-add',
    question: 'How do I add Centre 120 when I book?',
    answer:
      'Easiest way: use the <strong>Book a Trinity Exam</strong> button on this page — for digital bookings, 120 is pre-filled in the referral code box for you (just check it\'s still there before paying). If you book with Trinity directly, type <strong>120</strong> in the "referral code" field at checkout. Face-to-face exams at the Liverpool or Wirral venues are linked to Centre 120 automatically — no code needed.',
  },
  {
    id: 'digital-or-f2f',
    question: "Digital or face-to-face — what's the difference?",
    answer:
      'A digital exam is recorded and uploaded to Trinity, so it can be sat anywhere. A face-to-face exam is taken in person with an examiner at a venue. Both are full Trinity exams with the same syllabus and the same certificate — it is purely about how the candidate prefers to sit it.',
  },
  {
    id: 'booking-for-myself',
    question: "I'm booking for myself, not a child — is that fine?",
    answer:
      'Absolutely. Centre 120 is for anyone sitting a Trinity music exam — a parent booking for their child, an adult learner booking for themselves, or an older student booking independently. The process is exactly the same whoever the candidate is.',
  },
  {
    id: 'what-candidate-gets',
    question: 'What does the candidate get for being entered through Centre 120?',
    answer:
      'Every candidate booked through Centre 120 is included in a free recognition programme. They get an extra personalised certificate from us — on top of their official Trinity certificate, and whatever the result — plus a place on the Recognition page and entry into the quarterly &pound;50 prize draw. It costs nothing, it is automatic, and it never changes the official Trinity result.',
  },
  {
    id: 'which-exams',
    question: 'Which exams does Centre 120 cover?',
    answer:
      'All Trinity music routes: digital practical, digital theory, and face-to-face Classical &amp; Jazz and Rock &amp; Pop — from Initial through Grade 8, plus diplomas.',
  },
]

// "Want to go deeper?" — buttons to the fuller guide pages, for the
// first-timer who wants more than the quick answers above. These pages
// also live in the nav's "More" menu; surfaced here as clear buttons so
// nobody has to go hunting.
const moreInfoLinks = [
  { icon: BookOpen, label: 'Exam guide', href: '/exam-guide?from=trinity-exam-information' },
  { icon: GraduationCap, label: 'Grades explained', href: '/exam-guide/grades-explained?from=trinity-exam-information' },
  { icon: ClipboardCheck, label: 'What to expect', href: '/exam-guide/what-to-expect?from=trinity-exam-information' },
  { icon: Monitor, label: 'Digital exams', href: '/exam-guide/digital-exams?from=trinity-exam-information' },
  { icon: Receipt, label: 'Exam fees', href: '/exam-fees?from=trinity-exam-information' },
  { icon: HelpCircle, label: 'Full FAQ', href: '/faq?from=trinity-exam-information' },
]
</script>

<template>
  <Head :title="pageMeta.title" :description="pageMeta.description" />

  <div class="min-h-screen bg-black text-brand-text">
    <Navbar />

    <!-- ────────── OPENER — WHAT DO YOU NEED HELP WITH? ────────── -->
    <!-- Paul's brief: a self-selection at the very top so each kind of
         visitor is pointed the right way. Card 1 (first-timers) is who
         this whole page is built for — it just scrolls them in. Cards 2
         and 3 expand to a sub-choice and route onward. -->
    <section class="bg-brand-surface pt-36 pb-12 md:pt-40 lg:pt-40">
      <div class="mx-auto max-w-4xl px-4 sm:px-6">
        <div class="mb-6">
          <Breadcrumbs :pages="breadcrumbPages" home-href="/" />
        </div>

        <div :class="animClass('fade-up', 0)" class="text-center">
          <MyTextConstructor
            variant="heading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            class="md:!text-3xl lg:!text-4xl"
          >
            <template #myTitle>What do you need help with?</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-xl text-base text-brand-text-soft sm:text-lg">
            Pick whichever sounds most like you.
          </p>
        </div>

        <div :class="animClass('fade-up', 1)" class="mx-auto mt-8 max-w-2xl space-y-4">
          <template v-for="card in helpCards" :key="card.id">
            <!-- Card type "scroll": a plain anchor down into the page -->
            <a
              v-if="card.type === 'scroll'"
              :href="card.target"
              class="flex items-center gap-3 rounded-2xl border-2 border-brand-accent bg-brand-bg p-5 text-left transition hover:bg-brand-surface-soft sm:p-6"
            >
              <span class="flex-1 text-base font-semibold text-brand-text sm:text-lg">{{ card.label }}</span>
              <ArrowRight class="h-5 w-5 shrink-0 text-brand-accent" />
            </a>

            <!-- Card type "choice": expands to a sub-choice and routes on -->
            <div
              v-else
              class="overflow-hidden rounded-2xl border-2 border-brand-accent bg-brand-bg"
            >
              <button
                type="button"
                :aria-expanded="openHelpCard === card.id"
                class="flex w-full items-center gap-3 p-5 text-left transition hover:bg-brand-surface-soft sm:p-6"
                @click="toggleHelpCard(card.id)"
              >
                <span class="flex-1 text-base font-semibold text-brand-text sm:text-lg">{{ card.label }}</span>
                <ChevronDown
                  :class="[
                    'h-5 w-5 shrink-0 text-brand-accent transition-transform duration-200',
                    openHelpCard === card.id ? 'rotate-180' : '',
                  ]"
                />
              </button>

              <div
                class="overflow-hidden transition-all duration-300 ease-in-out"
                :class="openHelpCard === card.id ? 'max-h-[32rem] opacity-100' : 'max-h-0 opacity-0'"
              >
                <div class="border-t border-brand-border px-5 pb-5 pt-4 sm:px-6">
                  <p class="mb-3 text-sm font-semibold uppercase tracking-wide text-brand-text-soft">
                    {{ card.prompt }}
                  </p>
                  <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                    <Link
                      v-for="opt in card.options"
                      :key="opt.label"
                      :href="opt.href"
                      class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-accent px-5 py-3 text-base font-semibold text-brand-text-inverse transition hover:opacity-90"
                    >
                      {{ opt.label }}
                      <ArrowRight class="h-4 w-4 shrink-0" />
                    </Link>
                  </div>
                </div>
              </div>
            </div>
          </template>
        </div>
      </div>
    </section>

    <!-- ────────── HERO ────────── -->
    <section id="get-started" class="scroll-mt-32 bg-brand-surface py-12 sm:py-16">
      <div class="mx-auto max-w-4xl px-4 sm:px-6">
        <div class="text-center">
          <div :class="animClass('fade-up', 0)">
            <MyTextConstructor variant="eyebrow" alignment="center" spacing="tight">
              <template #myTitle>Trinity Registered Public Exam Centre 120</template>
            </MyTextConstructor>
          </div>

          <div :class="animClass('fade-up', 1)">
            <MyTextConstructor
              variant="heading"
              fontFamily="display"
              alignment="center"
              spacing="tight"
              class="mt-3 md:!text-3xl lg:!text-4xl"
            >
              <template #myTitle>Booking a Trinity music exam? Here's how to do it through Centre 120.</template>
            </MyTextConstructor>
          </div>

          <div :class="animClass('fade-up', 2)">
            <p class="mx-auto mt-5 max-w-2xl text-base leading-relaxed text-brand-text-soft sm:text-lg md:text-xl">
              New to booking a Trinity music exam? You're in the right place — this page walks you through it, one step at a time.
            </p>
          </div>

          <div :class="animClass('fade-up', 3)" class="mt-8 flex flex-col items-center justify-center gap-4 sm:flex-row sm:gap-6">
            <MyButtonConstructor size="large" variant="primary" @click="showBookingModal = true">
              Book a Trinity Exam
            </MyButtonConstructor>
            <a href="#how-it-works">
              <MyButtonConstructor size="large" variant="outline">
                How it works
              </MyButtonConstructor>
            </a>
          </div>
        </div>
      </div>
    </section>

    <!-- ────────── TRUST BAND ────────── -->
    <!-- The credibility line: search traffic doesn't need persuading they
         want a Trinity exam — it needs reassurance this is the legitimate
         route. Factual, not a pitch. -->
    <section class="bg-brand-surface py-10 sm:py-12">
      <div :class="animClass('fade-up', 1)" class="mx-auto max-w-4xl px-4 sm:px-6">
        <div class="flex flex-col items-center gap-6 sm:flex-row sm:gap-8">
          <div class="shrink-0">
            <div class="flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-br from-brand-primary via-brand-accent to-brand-primary shadow-lg sm:h-24 sm:w-24">
              <CheckCircle class="h-10 w-10 text-white sm:h-12 sm:w-12" />
            </div>
          </div>
          <div class="text-center sm:text-left">
            <p class="text-lg font-bold text-brand-primary sm:text-xl">
              Welcome to the Liverpool and Wirral Trinity Exam Centre
            </p>
            <p class="mt-2 text-base leading-relaxed text-brand-text-soft sm:text-base md:text-lg">
              You can book a <strong>digital</strong> exam with us or a <strong>face-to-face</strong> exam — either way, we'll make you feel welcome and help you as much as we can. We've been running Trinity exams across Liverpool and the Wirral for over 30 years.
            </p>
            <p class="mt-3 text-base leading-relaxed text-brand-text-soft sm:text-base md:text-lg">
              The code for booking digital exams is <span class="inline-block rounded-full bg-brand-accent/15 px-3 py-0.5 font-bold text-brand-accent">120</span> — make sure it's entered when you book a digital exam. That way we can support you, guide you, and give you the extra goodies we like to give people here in Liverpool and the Wirral.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- ────────── HOW IT WORKS ────────── -->
    <section
      id="how-it-works"
      class="relative scroll-mt-24"
      style="background-image: url('https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/blue_BG_9.jpg'); background-size: cover; background-position: center;"
    >
      <div class="absolute inset-0 bg-brand-primary/50" />
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
            <template #myTitle>How it works</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-2xl text-center text-base text-white/80 sm:text-base md:text-lg lg:text-xl">
            Doing this for the first time? It's three straightforward steps — here's exactly what to do.
          </p>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-10 space-y-6">
          <div
            v-for="item in steps"
            :key="item.step"
            class="flex gap-4 rounded-2xl border-4 border-brand-accent bg-white/10 p-5 shadow-2xl backdrop-blur-sm sm:gap-6 sm:p-6"
          >
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-brand-primary via-brand-accent to-brand-primary text-lg font-bold text-white shadow-md sm:h-12 sm:w-12 sm:text-xl">
              {{ item.step }}
            </div>
            <div>
              <p class="text-lg font-semibold text-white sm:text-xl">{{ item.title }}</p>
              <p class="mt-2 text-base leading-relaxed text-white/80 sm:text-base md:text-lg" v-html="item.detail"></p>
            </div>
          </div>
        </div>

        <div :class="animClass('fade-up', 3)" class="mt-8 text-center">
          <MyButtonConstructor variant="light" size="large" @click="showBookingModal = true">
            Book a Trinity Exam
          </MyButtonConstructor>
        </div>
      </div>
    </section>

    <!-- ────────── THE DETAIL (expandable) ────────── -->
    <!-- Paul's brief: lean info + buttons on the surface, with the depth
         tucked into accordions that open only if the visitor wants them.
         Framed as information sections, not a tacked-on FAQ — the full
         FAQ still lives at /faq and is linked below. -->
    <section class="bg-black py-12 sm:py-16">
      <div class="mx-auto max-w-3xl px-4 sm:px-6">
        <div :class="animClass('fade-up', 1)" class="text-center">
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            textColor="text-white"
            class="md:!text-2xl lg:!text-3xl"
          >
            <template #myTitle>The detail — open up what you need</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-2xl text-base text-white/80 sm:text-lg">
            Everything else worth knowing, tucked away so it's there if you want it.
          </p>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-8">
          <MyAccordionConstructor
            :items="detailItems"
            size="small"
            header-bg-color="bg-gradient-to-r from-brand-primary via-brand-accent to-brand-primary"
            header-text-color="text-brand-text-inverse"
            header-hover-bg-color="hover:opacity-90"
            border-color="border-brand-primary"
            content-bg-color="bg-brand-surface"
          />
        </div>

        <p :class="animClass('fade-up', 3)" class="mt-6 text-center text-base text-white/80 sm:text-lg">
          More questions answered on our
          <Link href="/faq?from=trinity-exam-information" class="font-semibold text-brand-accent hover:underline">full FAQ</Link>, or see who's been recognised on the
          <Link href="/recognition?from=trinity-exam-information" class="font-semibold text-brand-accent hover:underline">Recognition page</Link>.
        </p>
      </div>
    </section>

    <!-- ────────── MORE INFORMATION ────────── -->
    <!-- Paul's brief: buttons for the visitor who wants more in-depth
         info. These pages also sit in the nav's "More" menu; surfaced
         here as clear buttons so a first-timer doesn't have to go
         hunting. -->
    <section class="bg-brand-surface py-12 sm:py-16">
      <div class="mx-auto max-w-4xl px-4 sm:px-6">
        <div :class="animClass('fade-up', 1)" class="text-center">
          <MyTextConstructor variant="eyebrow" alignment="center" spacing="tight">
            <template #myTitle>Want to go deeper?</template>
          </MyTextConstructor>
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            class="mt-3 md:!text-2xl lg:!text-3xl"
          >
            <template #myTitle>More information, whenever you need it</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-2xl text-base text-brand-text-soft sm:text-lg">
            Full guides on everything to do with Trinity music exams — open any of these for the detail.
          </p>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-10 grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 lg:grid-cols-3">
          <Link
            v-for="link in moreInfoLinks"
            :key="link.label"
            :href="link.href"
            class="flex items-center gap-3 rounded-xl border border-brand-border bg-brand-bg p-4 transition hover:border-brand-accent hover:shadow-md sm:p-5"
          >
            <component :is="link.icon" class="h-6 w-6 shrink-0 text-brand-accent sm:h-7 sm:w-7" />
            <span class="flex-1 text-base font-semibold text-brand-text sm:text-lg">{{ link.label }}</span>
            <ArrowRight class="h-5 w-5 shrink-0 text-brand-accent" />
          </Link>
        </div>
      </div>
    </section>

    <!-- ────────── FINAL CTA — BOOK + CHECKLIST ────────── -->
    <section
      class="relative"
      style="background-image: url('https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/blue_BG_5.jpg'); background-size: cover; background-position: center;"
    >
      <div class="absolute inset-0 bg-brand-primary/30" />
      <div class="relative mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)" class="text-center">
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            textColor="text-white"
            class="md:!text-2xl lg:!text-3xl"
          >
            <template #myTitle>Ready to book?</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-xl text-base text-white/80 sm:text-lg md:text-xl">
            Book your Trinity exam now, or grab the free Trinity Exam Checklist — a step-by-step guide to booking through Centre 120.
          </p>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-8 text-center">
          <MyButtonConstructor variant="light" size="large" @click="showBookingModal = true">
            Book a Trinity Exam
          </MyButtonConstructor>
        </div>

        <div :class="animClass('fade-up', 3)" class="mt-8">
          <LeadMagnetCapture variant="dark" />
        </div>
      </div>
    </section>

    <MyFooter variant="gradient" />

    <!-- BookingModal — controlled by useBookingModal composable.
         Uses :show + @close (not v-model) — matches BookingModal's API. -->
    <BookingModal :show="showBookingModal" @close="showBookingModal = false" />
  </div>
</template>
