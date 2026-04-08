<!-- resources/js/pages/Incentives.vue -->
<script setup lang="ts">
import { ref } from 'vue'
import { usePageAnimation } from '@/composables/usePageAnimation'
import Head from '@/components/layouts/Head.vue'
import Navbar from '@/components/layouts/Navbar.vue'
import Breadcrumbs from '@/components/layouts/Breadcrumbs.vue'
import BookingModal from '@/components/BookingModal.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MyGlassCardConstructor from '@/components/reusables/MyGlassCardConstructor.vue'
import MyAccordionConstructor from '@/components/reusables/MyAccordionConstructor.vue'
import MyFooter from '@/components/layouts/MyFooter.vue'
import { Trophy, Award, Star, Gift, Users, CheckCircle } from 'lucide-vue-next'

const { animClass } = usePageAnimation()
const showBookingModal = ref(false)

const pageMeta = {
  title: 'Incentives — musicExams.help',
  description:
    'Every student entered through centre 120 gets recognised. Certificates, Hall of Fame, gift tokens, prize draws and teacher badges — all at no extra cost.',
}

const breadcrumbPages = [
  { name: 'Incentives', href: '/incentives', current: true },
]

/* ── Student incentive tiers ── */
const studentTiers = [
  {
    icon: Star,
    title: 'Recognition page',
    subtitle: 'Every student',
    detail: 'Every student entered through centre 120 — whether face-to-face or digital — receives at least a <strong>Bravo Certificate</strong> and gets listed on our Recognition page. First name and initial, instrument and grade. Full name shown only with permission.',
    link: '/recognition?from=incentives',
    linkText: 'See the Recognition page',
  },
  {
    icon: Award,
    title: 'Hall of Fame',
    subtitle: 'Merit and Distinction',
    detail: 'Score a Merit and earn a <strong>Take a Bow Certificate</strong>. Score a Distinction and earn a <strong>Standing Ovation Certificate</strong>. Every Merit and Distinction student gets celebrated in the Hall of Fame.',
    link: '/recognition?from=incentives',
    linkText: 'See the Hall of Fame',
  },
  {
    icon: Trophy,
    title: 'Top Scorers',
    subtitle: 'Quarterly — the very best',
    detail: 'The highest Distinction each quarter earns a <strong>Showstopper Certificate</strong>. The highest Merit earns a <strong>Centre Stage Certificate</strong>. Both earn the top spot on the Hall of Fame — plus a gift token (£20, or divided equally if there is a tie — minimum £5 each).',
    link: '/recognition?from=incentives',
    linkText: 'See the Hall of Fame',
  },
  {
    icon: Gift,
    title: 'Prize draws',
    subtitle: 'Automatic entry',
    detail: 'Every exam entry through centre 120 — face-to-face or digital — earns an automatic entry into our quarterly prize draw. Just by sitting your exam, you\'re in with a chance to win.',
  },
]

/* ── Teacher badges ── */
const teacherTiers = [
  {
    icon: Award,
    title: 'Teacher Appreciation Awards',
    subtitle: 'Four tiers to earn',
    detail: 'Every exam entry counts towards your award — regardless of the result and regardless of who booked it. Totals refresh every quarter. Bronze (10+), Silver (20+), Gold (30+) and Top Award (40+).',
    link: '/for-teachers/awards?from=incentives',
    linkText: 'View Teacher Awards',
  },
  {
    icon: Users,
    title: 'Your students get celebrated',
    subtitle: 'Through centre 120',
    detail: 'When your students enter through centre 120, every one of them receives at least a Bravo Certificate and gets listed on the Recognition page. Merit earns a Take a Bow Certificate and Distinction earns a Standing Ovation Certificate instead, and every entry goes into prize draws — all at no cost to you or them.',
    link: '/for-teachers?from=incentives',
    linkText: 'Find out more for teachers',
  },
]

/* ── How it works steps ── */
const howItWorks = [
  'Book any Trinity exam — face-to-face or digital — using centre code 120',
  'Your child sits the exam and the result comes through as normal',
  'Every entry earns at least a <strong>Bravo Certificate</strong> and gets listed on the Recognition page',
  'Merit and Distinction students are added to the <strong>Hall of Fame</strong> with a Take a Bow or Standing Ovation Certificate',
  'The highest scorers each quarter earn a <strong>Showstopper</strong> or <strong>Centre Stage Certificate</strong> and a gift token',
  'Every entry goes into a quarterly prize draw',
]

/* ── FAQs ── */
const faqs = [
  {
    id: 1,
    question: 'Does it cost anything extra to get these incentives?',
    answer: 'No. Everything on this page is included at no extra cost when you book through centre 120. The exam fees are the standard Trinity prices.',
  },
  {
    id: 2,
    question: 'What are the certificates?',
    answer: 'Every student entered through centre 120 receives at least a Bravo Certificate. Score a Merit and you receive a Take a Bow Certificate instead. Score a Distinction and you receive a Standing Ovation Certificate instead. The highest scorers each quarter earn the top awards — a Showstopper Certificate for the highest Distinction and a Centre Stage Certificate for the highest Merit. These are digital certificates you can print at home, display on a tablet or iPad for photos, or share straight to social media. No colour printer needed — just hold it up on the screen and snap a photo with your child. These are in addition to the official Trinity certificate that every Pass, Merit and Distinction receives.',
  },
  {
    id: 3,
    question: 'How does the prize draw work?',
    answer: 'Every exam entry through centre 120 — face-to-face or digital — goes into a quarterly prize draw. Winners are drawn at the end of each quarter and receive a gift token. You don\'t need to do anything extra — you\'re entered automatically.',
  },
  {
    id: 4,
    question: 'Can I opt out of the Recognition page?',
    answer: 'Yes. If you\'d prefer not to be listed, just let us know and we\'ll remove your entry. By default, only your first name and initial are shown — full names are only displayed with permission.',
  },
  {
    id: 5,
    question: 'How do teacher badges work?',
    answer: 'Every exam entry through centre 120 counts towards a teacher\'s award — regardless of the result and regardless of who booked it. There are four tiers: Bronze (10+), Silver (20+), Gold (30+) and Top Award (40+). Totals refresh every quarter.',
  },
  {
    id: 6,
    question: 'Do digital exams qualify?',
    answer: 'Yes. Every incentive on this page applies to both face-to-face and digital exams booked through centre 120. Digital exams count exactly the same.',
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
        <div class="text-center">
          <div :class="animClass('fade-up', 0)">
            <MyTextConstructor variant="eyebrow" alignment="center" spacing="tight">
              <template #myTitle>Incentives</template>
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
              <template #myTitle>More than just an exam</template>
            </MyTextConstructor>
          </div>

          <div :class="animClass('fade-up', 2)">
            <p class="mx-auto mt-4 max-w-2xl text-base text-brand-text-soft sm:text-base md:text-lg lg:text-xl">
              Every student entered through centre 120 gets recognised — certificates, prize draws,
              gift tokens and a place on our Recognition page. All at no extra cost.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- STUDENT ACHIEVEMENT BADGES -->
    <section class="bg-black">
      <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
        <div :class="animClass('zoom-in', 1)" class="grid grid-cols-3 gap-4">
          <div class="overflow-hidden rounded-2xl bg-white/10 p-2 shadow-2xl backdrop-blur-sm">
            <img
              src="https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/awardSR_1.png"
              alt="Student Achievement — Recognition badge"
              class="block w-full object-contain"
            />
          </div>
          <div class="overflow-hidden rounded-2xl bg-white/10 p-2 shadow-2xl backdrop-blur-sm">
            <img
              src="https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/awardSR_2.png"
              alt="Student Achievement — Take a Bow badge"
              class="block w-full object-contain"
            />
          </div>
          <div class="overflow-hidden rounded-2xl bg-white/10 p-2 shadow-2xl backdrop-blur-sm">
            <img
              src="https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/awardSR_3.png"
              alt="Student Achievement — Standing Ovation badge"
              class="block w-full object-contain"
            />
          </div>
        </div>
        <p class="mt-3 text-center text-sm text-white/50">Bravo — Take a Bow — Standing Ovation</p>
      </div>
    </section>

    <!-- STUDENT INCENTIVES -->
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
            <template #myTitle>For students</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-2xl text-center text-base text-white/80 sm:text-base md:text-lg lg:text-xl">
            Every student gets something. The higher you score, the more you earn.
          </p>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-10">
          <MyGlassCardConstructor :cards="studentTiers" :columns="2" />
        </div>
      </div>
    </section>

    <!-- HOW IT WORKS -->
    <section class="bg-black">
      <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:py-16">
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

        <div :class="animClass('fade-up', 2)" class="mx-auto mt-8 max-w-xl">
          <div class="rounded-2xl border-4 border-brand-accent bg-white/10 p-5 shadow-2xl backdrop-blur-sm sm:p-6">
            <ul class="space-y-4">
              <li
                v-for="(step, index) in howItWorks"
                :key="index"
                class="flex items-start gap-3"
              >
                <CheckCircle class="mt-0.5 h-5 w-5 shrink-0 text-brand-accent" />
                <span class="text-base text-white/90 sm:text-base md:text-lg" v-html="step"></span>
              </li>
            </ul>
          </div>
        </div>

        <div :class="animClass('fade-up', 3)" class="mt-8 text-center">
          <MyButtonConstructor variant="light" size="large" @click="showBookingModal = true">
            Book Your Exam
          </MyButtonConstructor>
        </div>
      </div>
    </section>

    <!-- TEACHER INCENTIVES -->
    <section
      class="relative"
      style="background-image: url('https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/blue_BG_7.jpg'); background-size: cover; background-position: center;"
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
            <template #myTitle>For teachers</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-2xl text-center text-base text-white/80 sm:text-base md:text-lg lg:text-xl">
            Teachers are appreciated too. Every entry your students make counts towards your award.
          </p>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-10">
          <MyGlassCardConstructor :cards="teacherTiers" :columns="2" />
        </div>
      </div>
    </section>

    <!-- FAQ -->
    <section class="bg-black">
      <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
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
            :items="faqs"
            size="small"
            header-bg-color="bg-gradient-to-r from-brand-primary via-brand-accent to-brand-primary"
            header-text-color="text-brand-text-inverse"
            header-hover-bg-color="hover:opacity-90"
            border-color="border-brand-primary"
            content-bg-color="bg-brand-surface"
          />
        </div>

        <!-- CTA -->
        <div :class="animClass('fade-up', 3)" class="mt-10 text-center">
          <MyTextConstructor
            variant="button-lg"
            alignment="center"
            spacing="tight"
            textColor="text-white"
          >
            <template #myTitle>Ready to get started?</template>
          </MyTextConstructor>
          <p class="mx-auto mt-2 max-w-lg text-base text-white/80 sm:text-base">
            Book through centre 120 and your student qualifies for everything on this page — at no extra cost.
          </p>
          <div class="mt-4">
            <MyButtonConstructor variant="light" size="large" @click="showBookingModal = true">
              Book Your Exam
            </MyButtonConstructor>
          </div>
        </div>
      </div>
    </section>

    <!-- FOOTER -->
    <MyFooter variant="gradient" />

    <BookingModal :show="showBookingModal" @close="showBookingModal = false" />
  </div>
</template>
