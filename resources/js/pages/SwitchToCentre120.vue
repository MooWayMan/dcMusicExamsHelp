<!-- resources/js/pages/SwitchToCentre120.vue -->
<script setup lang="ts">
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
import MyRunnerConstructor from '@/components/reusables/MyRunnerConstructor.vue'
import MyGlassCardConstructor from '@/components/reusables/MyGlassCardConstructor.vue'
import MyAccordionConstructor from '@/components/reusables/MyAccordionConstructor.vue'
import MyFooter from '@/components/layouts/MyFooter.vue'
import {
  Award, Trophy, Gift, BookOpen, Star, MessagesSquare,
  PoundSterling, ChevronRight, CheckCircle,
} from 'lucide-vue-next'

const { animClass } = usePageAnimation()
const { showBookingModal } = useBookingModal()

const pageMeta = {
  title: 'Switch to Centre 120 — Trinity exams with recognition | musicExams.help',
  description:
    'Already booking Trinity digital exams? Enter centre 120 at checkout — same exam, same price — and your students unlock the Hall of Fame, prize draws and certificates. Free, takes seconds.',
}

const breadcrumbPages = [
  { name: 'Switch to Centre 120', href: '/switch-to-centre-120', current: true },
]

// 30-second pitch — three icon cards that summarise the offer.
const pitchCards = [
  {
    icon: PoundSterling,
    title: 'Same exam, same price',
    descript: 'Centre 120 is a fully registered Trinity exam centre. Your candidates take the exact same exam at the exact same fee — Trinity bills directly. No premium, no markup, no extra step.',
  },
  {
    icon: Trophy,
    title: 'Your students get celebrated',
    descript: 'Every candidate booked through centre 120 receives recognition — a Bravo certificate just for sitting, a Take a Bow for Merit, a Standing Ovation for Distinction. Top scorers each quarter join the Hall of Fame.',
  },
  {
    icon: Award,
    title: 'You earn Appreciation Awards',
    descript: 'Every entry counts towards your tiered Appreciation Award — Bronze (10+), Silver (20+), Gold (30+), Top Award (40+). Display the badge anywhere. Every entry also enters the £50 quarterly teacher prize draw.',
  },
]

// How it works — three numbered steps.
const steps = [
  {
    step: 1,
    title: "Book your student's Trinity exam as normal",
    detail: 'Use Trinity\'s digital booking system the way you always have. Pick the subject, grade, candidate and date. No new system to learn.',
  },
  {
    step: 2,
    title: 'Enter centre 120 in the referral code box',
    detail: 'On the Trinity booking screen, find the "referral code" box and type <strong>120</strong>. That\'s it — your student\'s entry is now linked to musicExams.help at no extra cost. <em>(Tip: refreshing the booking page can clear the code, so check it\'s still there before paying.)</em>',
  },
  {
    step: 3,
    title: 'Your student is automatically in the programme',
    detail: 'From that moment your student is on the Recognition page, eligible for the Hall of Fame, the prize draws, and tiered certificates. You start accruing toward your teacher Appreciation Award. Everything is automatic — we do the admin from results day onwards.',
  },
]

// Six benefit cards — what teachers and students actually get.
const benefits = [
  {
    icon: Award,
    title: 'Tiered Appreciation Awards',
    detail: 'Bronze at 10 candidates, Silver at 20, Gold at 30, Top Award at 40+. Display the badge on your website, email signature and social media. Each tier comes with a dated, printable certificate too.',
    link: '/for-teachers/awards?from=switch-to-centre-120',
    linkText: 'See the award tiers',
  },
  {
    icon: Trophy,
    title: 'Hall of Fame for every candidate',
    detail: 'The Recognition page lists every centre 120 candidate by first name and surname initial — pass, fail, or anywhere in between. Distinctions get a Standing Ovation Certificate, Merits get a Take a Bow Certificate.',
    link: '/recognition?from=switch-to-centre-120',
    linkText: 'Browse the Hall of Fame',
  },
  {
    icon: Gift,
    title: 'Prize draws — teachers AND students',
    detail: 'Every entry generates a ticket in both quarterly draws. £50 gift token to one teacher and one student per quarter. The more candidates you enter, the more chances to win. Auto-included — no admin from your end.',
    link: '/incentives?from=switch-to-centre-120',
    linkText: 'Prize draw details',
  },
  {
    icon: Star,
    title: 'Top-Scorer Certificates with gift tokens',
    detail: 'Each quarter the highest-scoring Distinction and the highest-scoring Merit in two grade groups (Initial–5 and 6–8) earn a Showstopper Certificate (Distinction) or Centre Stage Certificate (Merit) plus a gift token. Tied winners share, minimum £5 each.',
  },
  {
    icon: BookOpen,
    title: 'Trinity Exam Checklist + admin guidance',
    detail: 'A free, downloadable PDF that walks teachers through everything from booking flow to exam-day prep — with the bits Trinity\'s own materials skip. Designed for the realities of running entries through a small studio.',
  },
  {
    icon: MessagesSquare,
    title: 'Real human help when you need it',
    detail: 'Email us with any question — booking, results, repertoire, you name it. We answer within working hours. Trinity gives you the exam; centre 120 gives you the surrounding support.',
  },
]

// FAQ — capped at 4 (project rule). New questions go to /faq.
const faqItems = [
  {
    id: 'cost',
    question: 'Does using centre 120 cost anything extra?',
    answer: 'No. Trinity charges your candidate exactly the same fee whether or not centre 120 is on the booking. Your student pays Trinity direct. We receive a small reseller commission from Trinity (not from you, not from the candidate) which funds the recognition programme — prize draws, certificates, gift tokens.',
  },
  {
    id: 'how-to-add',
    question: 'How exactly do I add centre 120 at booking?',
    answer: 'On Trinity\'s digital booking screen, look for a field labelled "referral code". Type <strong>120</strong>. The system links the entry to our centre. If the page reloads (e.g. you go back), the code can clear — confirm it\'s still in the box before payment.',
  },
  {
    id: 'switch',
    question: 'I currently book with another centre. Can I switch?',
    answer: 'Yes — simply enter centre 120 on your next Trinity booking. There is no contract on either side, no formal switch needed. Each booking is independent. Your existing students with prior bookings under another centre stay where they are; new bookings flow through 120.',
  },
  {
    id: 'failed',
    question: "What if my student doesn't pass?",
    answer: 'Every centre 120 candidate gets a Bravo Certificate regardless of result and is listed on the Recognition page. We never publish failed scores — we only publish that they entered and sat the exam. The recognition is for the effort, not just the outcome.',
  },
]
</script>

<template>
  <Head :title="pageMeta.title" :description="pageMeta.description" />

  <div class="min-h-screen bg-black text-brand-text">
    <Navbar />

    <!-- ────────── HERO ────────── -->
    <section class="bg-brand-surface pt-36 pb-12 md:pt-40 lg:pt-40">
      <div class="mx-auto max-w-4xl px-4 sm:px-6">
        <div class="mb-6">
          <Breadcrumbs :pages="breadcrumbPages" home-href="/" />
        </div>

        <div class="text-center">
          <div :class="animClass('fade-up', 0)">
            <MyTextConstructor variant="eyebrow" alignment="center" spacing="tight">
              <template #myTitle>For UK Music Teachers</template>
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
              <template #myTitle>Already booking Trinity digital exams? You're leaving recognition on the table.</template>
            </MyTextConstructor>
          </div>

          <div :class="animClass('fade-up', 2)">
            <p class="mx-auto mt-5 max-w-2xl text-base leading-relaxed text-brand-text-soft sm:text-lg md:text-xl">
              Same exam. Same price. Add <span class="inline-block rounded-full bg-brand-accent/15 px-3 py-0.5 font-bold text-brand-accent">centre 120</span> at booking and your students unlock the Hall of Fame, prize draws and tiered certificates — for every candidate, even those who don't pass. Free. Takes one click at checkout.
            </p>
          </div>

          <div :class="animClass('fade-up', 3)" class="mt-8 flex flex-col items-center justify-center gap-4 sm:flex-row sm:gap-6">
            <a href="#get-checklist">
              <MyButtonConstructor size="large" variant="primary">
                Get the Exam Checklist
              </MyButtonConstructor>
            </a>
            <a href="#how-it-works">
              <MyButtonConstructor size="large" variant="outline">
                See how it works
              </MyButtonConstructor>
            </a>
          </div>
        </div>
      </div>
    </section>

    <!-- ────────── 30-SECOND PITCH ────────── -->
    <section class="bg-black py-12 sm:py-16">
      <div class="mx-auto max-w-5xl px-4 sm:px-6">
        <div :class="animClass('fade-up', 1)" class="text-center">
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            textColor="text-white"
            class="md:!text-2xl lg:!text-3xl"
          >
            <template #myTitle>The 30-second version</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-2xl text-base text-white/80 sm:text-lg md:text-xl">
            You're already paying Trinity. This just means your students get celebrated, and you get recognised for the work you do.
          </p>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-10">
          <MyRunnerConstructor
            :theArray="pitchCards"
            variant="icon"
            :columns="3"
            spacing="normal"
            maxWidth="4xl"
            :enableHover="false"
          />
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
            Three steps. No new admin. Same Trinity booking flow you already use.
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
          <p class="text-base text-white/80 sm:text-lg md:text-xl">
            That's it. Same exam — plus everything below.
          </p>
        </div>
      </div>
    </section>

    <!-- ────────── WHAT YOU GET ────────── -->
    <section
      class="relative"
      style="background-image: url('https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/blue_BG_5.jpg'); background-size: cover; background-position: center;"
    >
      <div class="absolute inset-0 bg-brand-primary/20" />
      <div class="relative mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)" class="text-center">
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            textColor="text-white"
            class="md:!text-2xl lg:!text-3xl"
          >
            <template #myTitle>What you and your students get</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-2xl text-base text-white/80 sm:text-lg md:text-xl">
            All included with centre 120 — at no cost to you or your students.
          </p>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-10">
          <MyGlassCardConstructor :cards="benefits" :columns="2" />
        </div>
      </div>
    </section>

    <!-- ────────── TRUST BAND ────────── -->
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
              Trinity College London Registered Public Exam Centre — code 120
            </p>
            <p class="mt-2 text-base leading-relaxed text-brand-text-soft sm:text-base md:text-lg">
              Centre 120 covers all four Trinity booking routes: digital practical, digital theory, Classical &amp; Jazz face-to-face, and Rock &amp; Pop face-to-face. Active since 2020. Run by Paul Sheridan, Wirral-based music teacher and Trinity Registered Public Exam Centre Representative.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- ────────── FAQ ────────── -->
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
            <template #myTitle>Common questions</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-8">
          <MyAccordionConstructor
            :items="faqItems"
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
          <Link href="/faq?from=switch-to-centre-120" class="font-semibold text-brand-accent hover:underline">full FAQ</Link>.
        </p>
      </div>
    </section>

    <!-- ────────── FINAL CTA — LEAD MAGNET + BOOKING ────────── -->
    <section
      id="get-checklist"
      class="relative scroll-mt-24"
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
            <template #myTitle>Ready to claim it?</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-xl text-base text-white/80 sm:text-lg md:text-xl">
            Download the Trinity Exam Checklist — a free guide to running entries through centre 120 — or book a Trinity exam now.
          </p>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-8">
          <LeadMagnetCapture variant="dark" />
        </div>

        <div :class="animClass('fade-up', 3)" class="mt-8 text-center">
          <p class="mb-4 text-base text-white/80 sm:text-lg">Or skip ahead and book straight away:</p>
          <MyButtonConstructor variant="light" size="large" @click="showBookingModal = true">
            Book a Trinity Exam
          </MyButtonConstructor>
        </div>
      </div>
    </section>

    <MyFooter variant="gradient" />

    <!-- BookingModal — controlled by useBookingModal composable. -->
    <BookingModal v-model="showBookingModal" />
  </div>
</template>
