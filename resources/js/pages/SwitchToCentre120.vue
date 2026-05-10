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
import MyGlassCardConstructor from '@/components/reusables/MyGlassCardConstructor.vue'
import MyAccordionConstructor from '@/components/reusables/MyAccordionConstructor.vue'
import MyFooter from '@/components/layouts/MyFooter.vue'
import {
  Award, Trophy, Gift, BookOpen, Star, MessagesSquare,
  PoundSterling, CheckCircle, LayoutDashboard,
  CalendarDays, Receipt, ClipboardCheck, GraduationCap, ScrollText, ListMusic,
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

// 30-second pitch — three feature cards that summarise the offer.
// Each card is structured as: intro line, label/detail items (rendered
// as brand-accent mini-headings + description below), and an outro line.
// This gives all three cards equal visual weight and avoids the messy
// inline-list look of the previous version.
const pitchCards = [
  {
    icon: PoundSterling,
    title: 'Same exam, same price',
    intro: 'Centre 120 is a fully registered Trinity exam centre. Your students get:',
    items: [
      { label: 'Same exam', detail: 'Identical to booking with Trinity direct' },
      { label: 'Same fee', detail: 'Trinity bills directly — no markup' },
      { label: 'Same certificate', detail: 'Issued by Trinity College London' },
    ],
    outro: 'The only thing that changes: your students unlock everything on this page.',
  },
  {
    icon: Trophy,
    title: 'Your students get celebrated',
    intro: 'Every candidate booked through centre 120 receives recognition:',
    items: [
      { label: 'Bravo Certificate', detail: 'Every candidate, just for sitting' },
      { label: 'Take a Bow Certificate', detail: 'Merit' },
      { label: 'Standing Ovation Certificate', detail: 'Distinction' },
    ],
    outro: 'Top scorers each quarter join the Hall of Fame and earn a gift token.',
  },
  {
    icon: Award,
    title: 'You earn Appreciation Awards',
    intro: 'Every entry counts toward your tiered Appreciation Award:',
    items: [
      { label: 'Bronze', detail: '10+ candidates' },
      { label: 'Silver', detail: '20+ candidates' },
      { label: 'Gold', detail: '30+ candidates' },
      { label: 'Top Award', detail: '40+ candidates' },
    ],
    outro: 'Display the badge anywhere parents see your work.',
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
// Outbound links deliberately omitted on this paid-traffic page so the
// only clear next action is the lead magnet or the booking button.
const benefits = [
  {
    icon: Award,
    title: 'Tiered Appreciation Awards',
    detail: 'Bronze at 10 candidates, Silver at 20, Gold at 30, Top Award at 40+. Display the badge on your website, email signature and social media. Each tier comes with a dated, printable certificate too.',
  },
  {
    icon: Trophy,
    title: 'Hall of Fame for every candidate',
    detail: 'The Recognition page lists every centre 120 candidate by first name and surname initial — pass, fail, or anywhere in between. Distinctions get a Standing Ovation Certificate, Merits get a Take a Bow Certificate.',
  },
  {
    icon: Gift,
    title: 'Prize draws — teachers AND students',
    detail: 'Every entry generates a ticket in both quarterly draws. £50 gift token to one teacher and one student per quarter. The more candidates you enter, the more chances to win. Auto-included — no admin from your end.',
  },
  {
    icon: Star,
    title: 'Top-Scorer Certificates with gift tokens',
    detail: 'Each quarter the highest-scoring Distinction and the highest-scoring Merit in two grade groups (Initial–5 and 6–8) earn a Showstopper Certificate (Distinction) or Centre Stage Certificate (Merit) plus a gift token. Tied winners share, minimum £5 each.',
  },
  {
    icon: BookOpen,
    title: 'Save hours of parent admin every term',
    detail: 'musicExams.help is a full guidance library — exam dates, fees, what to expect on the day, grades explained, UCAS points, syllabus details, FAQs, repertoire notes. Send your parents and students here instead of fielding the same questions yourself every term. Plus the Trinity Exam Checklist PDF as a downloadable cheat sheet for the booking flow.',
  },
  {
    icon: LayoutDashboard,
    title: 'Your free teacher dashboard',
    detail: 'Sign up free and see every centre 120 student in one place — bookings, exam dates, results as they land, plus live progress toward your next Appreciation Award and your current prize-draw ticket count. No spreadsheets, no hunting around Trinity\'s portal.',
  },
]

// "What's on the site" peek — visual proof of the guidance library
// depth, without outbound links (per landing-page conversion rule).
const guidancePages = [
  { icon: CalendarDays, label: 'Exam dates' },
  { icon: Receipt, label: 'Exam fees' },
  { icon: ClipboardCheck, label: 'What to expect' },
  { icon: GraduationCap, label: 'Grades explained' },
  { icon: ScrollText, label: 'UCAS points' },
  { icon: ListMusic, label: 'Syllabus details' },
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
    answer: 'Easiest: click the <strong>Book a Trinity Exam</strong> button on this page — for digital bookings we automatically pre-fill <strong>120</strong> in the referral code field for you. Just verify it\'s still in the box before paying, as a page refresh can occasionally clear it. Or, if you book through Trinity directly, type <strong>120</strong> in the "referral code" field at checkout. Face-to-face bookings at our Liverpool or Wirral venues are linked to centre 120 automatically — no code needed.',
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
            <MyButtonConstructor size="large" variant="primary" @click="showBookingModal = true">
              Book a Trinity Exam
            </MyButtonConstructor>
            <a href="#what-you-get">
              <MyButtonConstructor size="large" variant="outline">
                See what you get
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

        <div :class="animClass('fade-up', 2)" class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-3">
          <div
            v-for="card in pitchCards"
            :key="card.title"
            class="flex flex-col overflow-hidden rounded-2xl border-4 border-brand-accent shadow-2xl"
          >
            <!-- Header bar — gradient with icon and title.
                 Mobile: header auto-sizes (cards stack, equal heights
                 invisible). Desktop (sm+): min-h ensures single-line
                 and two-line titles fill the same physical height so
                 all three card headers align. -->
            <div class="flex items-center gap-3 bg-gradient-to-r from-brand-primary via-brand-accent to-brand-primary px-5 py-3 sm:min-h-[5rem] sm:px-6">
              <component :is="card.icon" class="h-5 w-5 shrink-0 text-white sm:h-6 sm:w-6" />
              <p class="text-base font-semibold text-white sm:text-lg">{{ card.title }}</p>
            </div>
            <!-- Body — flex-1 so footer aligns across cards regardless of body length -->
            <div class="flex-1 bg-black p-5 sm:p-6">
              <p class="text-base leading-relaxed text-white/80 sm:text-base md:text-lg">{{ card.intro }}</p>
              <div class="mt-4 space-y-3">
                <div v-for="item in card.items" :key="item.label">
                  <p class="text-base font-bold text-brand-accent sm:text-lg">{{ item.label }}</p>
                  <p class="mt-0.5 text-sm leading-relaxed text-white/80 sm:text-base">{{ item.detail }}</p>
                </div>
              </div>
            </div>
            <!-- Footer — gradient bar carrying the outro line.
                 Mobile: footer auto-sizes to its own content (cards stack,
                 so equal-heights-across-cards is invisible to users — and
                 a forced min-h would just create empty space below short
                 outros on cards 1 and 2).
                 Desktop (sm+): cards sit side-by-side, so we equalise
                 footer heights with min-h so all three end at the same
                 vertical position. -->
            <div class="flex items-center bg-gradient-to-r from-brand-primary via-brand-accent to-brand-primary px-5 py-4 sm:min-h-[8rem] sm:px-6">
              <p class="text-sm leading-relaxed text-white sm:text-base">{{ card.outro }}</p>
            </div>
          </div>
        </div>

        <!-- Parent-admin note. Discreet callout strip beneath the three pitch
             cards — explains that even when parents do the booking, the teacher
             still gets credited toward their tally if the parent emails us
             with the teacher's name. Not in a card so it doesn't clutter the
             pitch grid; sits as a small standalone strip. -->
        <div :class="animClass('fade-up', 3)" class="mx-auto mt-8 max-w-3xl rounded-xl border border-brand-accent/40 bg-brand-accent/10 px-5 py-4 sm:px-6">
          <div class="flex items-start gap-3">
            <MessagesSquare class="h-5 w-5 shrink-0 text-brand-accent sm:h-6 sm:w-6" />
            <p class="text-sm leading-relaxed text-white/90 sm:text-base">
              <span class="font-semibold text-white">Parent doing the booking?</span>
              No problem — just have them email
              <a href="mailto:musicexams@musicexams.help" class="font-semibold text-brand-accent underline hover:opacity-80">musicexams@musicexams.help</a>
              with your name and we'll link the entry to your tally.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- ────────── PRIZE DRAW HOOK ────────── -->
    <!-- CTA here is intentionally the booking path (not the lead-magnet)
         because it directly matches the panel's narrative: every booking
         is a draw entry. Gives the booking path a mid-page touchpoint
         while the lead-magnet CTA stays in the hero + final-CTA sections. -->
    <section class="bg-brand-danger py-12 sm:py-16">
      <div :class="animClass('zoom-in', 1)" class="mx-auto max-w-4xl px-4 text-center sm:px-6">
        <Gift class="mx-auto mb-4 h-12 w-12 text-white sm:h-16 sm:w-16" />
        <p class="text-lg font-bold uppercase tracking-widest text-white/80 sm:text-xl">Quarterly Prize Draw</p>
        <p class="mt-3 text-4xl font-extrabold text-white sm:text-5xl md:text-6xl">Win a £50 Gift Token</p>
        <p class="mx-auto mt-4 max-w-2xl text-base leading-relaxed text-white/90 sm:text-lg md:text-xl">
          Every exam you book through centre 120 — face-to-face, digital or theory — earns you an automatic entry into our quarterly teacher prize draw. The more students you enter, the more chances to win.
        </p>
        <div class="mt-8">
          <MyButtonConstructor variant="light" size="large" @click="showBookingModal = true">
            Book a Trinity Exam
          </MyButtonConstructor>
        </div>
      </div>
    </section>

    <!-- ────────── CERTIFICATES SHOWCASE ────────── -->
    <!-- Visual proof of the student certificate tiers. Three smiling
         kids holding Bravo, Centre Stage and Showstopper certificates.
         Sits before the What You Get section so readers see the
         physical artefacts before reading the benefit names. -->
    <section class="bg-black py-12 sm:py-16">
      <div class="mx-auto max-w-4xl px-4 sm:px-6">
        <div :class="animClass('zoom-in', 1)" class="overflow-hidden rounded-2xl shadow-2xl">
          <img
            src="https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/three-tier-certificates.jpg"
            alt="Three students holding their musicExams.help certificates — Centre Stage, Showstopper and Bravo — earned through centre 120 Trinity exams"
            class="block w-full"
            loading="lazy"
          />
        </div>
        <p :class="animClass('fade-up', 2)" class="mx-auto mt-6 max-w-2xl text-center text-base text-white/80 sm:text-lg md:text-xl">
          Every candidate at centre 120 gets recognised — from sitting the exam (Bravo) to top quarterly scorers (Showstopper and Centre Stage). Plus Take a Bow for Merit and Standing Ovation for Distinction.
        </p>
      </div>
    </section>

    <!-- ────────── WHAT YOU GET ────────── -->
    <!-- Moved up: benefits-before-mechanics order. Hero secondary CTA
         anchors to #what-you-get so a curious-but-unconvinced reader
         lands directly in the full menu of value. -->
    <section
      id="what-you-get"
      class="relative scroll-mt-24"
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

    <!-- ────────── BEHIND THE SCENES PEEK ────────── -->
    <!-- Visual proof of the depth of the guidance library, without
         actually linking out (per landing-page rule: keep users on
         the conversion funnel). Reinforces the 'Save hours of parent
         admin' benefit card directly above. -->
    <section class="bg-brand-surface py-12 sm:py-16">
      <div class="mx-auto max-w-4xl px-4 sm:px-6">
        <div :class="animClass('fade-up', 1)" class="text-center">
          <MyTextConstructor variant="eyebrow" alignment="center" spacing="tight">
            <template #myTitle>What's already on the site</template>
          </MyTextConstructor>
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            class="mt-3 md:!text-2xl lg:!text-3xl"
          >
            <template #myTitle>The questions parents ask — already answered</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-2xl text-base text-brand-text-soft sm:text-lg md:text-xl">
            Send your parents and students here and reclaim your evenings.
          </p>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-10 grid grid-cols-2 gap-4 sm:grid-cols-3 sm:gap-6 lg:grid-cols-6">
          <div
            v-for="page in guidancePages"
            :key="page.label"
            class="flex flex-col items-center gap-2 rounded-xl border border-brand-border bg-brand-bg p-4 text-center sm:p-5"
          >
            <component :is="page.icon" class="h-7 w-7 text-brand-accent sm:h-8 sm:w-8" />
            <p class="text-sm font-semibold text-brand-text sm:text-base">{{ page.label }}</p>
          </div>
        </div>

        <p :class="animClass('fade-up', 3)" class="mt-6 text-center text-sm text-brand-text-soft sm:text-base">
          Plus the FAQ, the Hall of Fame, and the Trinity Exam Checklist PDF.
        </p>
      </div>
    </section>

    <!-- ────────── HOW IT WORKS ────────── -->
    <!-- Moved down: now positioned as the "great, so how do I do this?"
         beat after the reader is sold on the benefits. -->
    <section
      class="relative"
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
            That's it. Same exam — plus everything we've shown you.
          </p>
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
              Centre 120 covers all four Trinity booking routes: digital practical, digital theory, Classical &amp; Jazz face-to-face, and Rock &amp; Pop face-to-face. Run by Paul Sheridan, Wirral-based music teacher and Trinity Registered Public Exam Centre Representative — running Trinity exams in the Liverpool and Wirral area for over 30 years.
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

    <!-- BookingModal — controlled by useBookingModal composable.
         Uses :show + @close (not v-model) — matches BookingModal's API. -->
    <BookingModal :show="showBookingModal" @close="showBookingModal = false" />
  </div>
</template>
