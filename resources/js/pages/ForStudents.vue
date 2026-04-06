<!-- resources/js/pages/ForStudents.vue -->
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
import MyFooter from '@/components/layouts/MyFooter.vue'
import { Trophy, Award, Star, Gift } from 'lucide-vue-next'

const { animClass } = usePageAnimation()
const showBookingModal = ref(false)

const pageMeta = {
  title: 'For Students — musicExams.help',
  description:
    'Your hard work deserves to be celebrated. See how students get recognised through musicExams.help centre code 120 — Hall of Fame, certificates, prize draws and more.',
}

const breadcrumbPages = [
  { name: 'For Students', href: '/for-students', current: true },
]

const hallOfFameLogo =
  'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/Highest+score+In5.png'

const tiers = [
  {
    icon: Trophy,
    title: 'Top Scorers',
    subtitle: 'Quarterly — the very best',
    detail: 'The highest Distinction and highest Merit score each quarter earn the top spot on the <strong>Hall of Fame</strong> — plus a gift token. This is the ultimate recognition for outstanding achievement.',
    link: '/thank-you',
    linkText: 'See the Hall of Fame',
  },
  {
    icon: Award,
    title: 'Hall of Fame',
    subtitle: 'Every Merit and Distinction',
    detail: 'Score a Merit and earn a Take a Bow Certificate. Score a Distinction and earn a Standing Ovation Certificate. Every Merit and Distinction student gets celebrated in the <strong>Hall of Fame</strong> — show it off, share it, put it on the wall.',
    link: '/thank-you',
    linkText: 'See the Hall of Fame',
  },
  {
    icon: Star,
    title: 'Thank You page',
    subtitle: 'Every student through centre 120',
    detail: 'Every student who enters an exam through centre 120 gets listed on our Thank You page — your first name and initial, instrument and grade. This can later be changed to show your full name with permission. Your hard work is always recognised.',
    link: '/thank-you',
    linkText: 'Visit the Thank You page',
  },
  {
    icon: Gift,
    title: 'Prize draws',
    subtitle: 'Automatic entry',
    detail: 'Every exam entry through centre 120 earns an automatic entry into our quarterly prize draw. Just by sitting your exam, you\'re in with a chance to win a gift token!',
    link: '/contact',
    linkText: 'Find out more',
  },
]

const resultsBands = [
  { label: 'Distinction', range: '87–100', color: 'bg-brand-accent', textColor: 'text-white' },
  { label: 'Merit', range: '75–86', color: 'bg-brand-success', textColor: 'text-white' },
  { label: 'Pass', range: '60–74', color: 'bg-brand-teal', textColor: 'text-white' },
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
              <template #myTitle>For Students</template>
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
              <template #myTitle>Your hard work deserves to be celebrated</template>
            </MyTextConstructor>
          </div>

          <div :class="animClass('fade-up', 2)">
            <p class="mx-auto mt-4 max-w-2xl text-base text-brand-text-soft sm:text-base md:text-lg lg:text-xl">
              When you sit a Trinity exam through centre 120, your achievement doesn't just disappear into a filing cabinet.
              You get recognised — on our website, with special certificates, and through prizes.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- HALL OF FAME BANNER -->
    <section class="bg-black">
      <div class="mx-auto max-w-4xl px-4 py-10 sm:px-6">
        <a href="/thank-you" :class="animClass('zoom-in', 1)" class="group block overflow-hidden rounded-2xl bg-black shadow-2xl transition-transform duration-300 hover:scale-[1.005]">
          <img
            :src="hallOfFameLogo"
            alt="Hall of Fame — Highest Score"
            class="h-auto w-full object-contain"
          />
        </a>
      </div>
    </section>

    <!-- RECOGNITION TIERS -->
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
            <template #myTitle>How you get recognised</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-2xl text-center text-base text-white/80 sm:text-base md:text-lg lg:text-xl">
            Every student gets something. The higher you score, the more you earn.
          </p>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-10">
          <MyGlassCardConstructor :cards="tiers" :columns="2" />
        </div>

        <!-- CTA to Thank You page -->
        <div :class="animClass('fade-up', 3)" class="mt-10 text-center">
          <a href="/thank-you">
            <MyButtonConstructor variant="light" size="large">
              Visit the Thank You Page
            </MyButtonConstructor>
          </a>
        </div>
      </div>
    </section>

    <!-- RESULTS EXPLAINED -->
    <section class="bg-brand-surface">
      <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            class="md:!text-2xl lg:!text-3xl"
          >
            <template #myTitle>Understanding your results</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-2xl text-center text-base text-brand-text-soft sm:text-base md:text-lg lg:text-xl">
            Trinity exams are marked out of 100. Here's what the results mean.
          </p>
        </div>

        <div :class="animClass('fade-up', 2)" class="mx-auto mt-8 max-w-sm space-y-3">
          <div
            v-for="band in resultsBands"
            :key="band.label"
            :class="[band.color, band.textColor, 'flex items-center justify-between rounded-xl px-5 py-3 font-semibold shadow-sm']"
          >
            <span class="text-base sm:text-lg">{{ band.label }}</span>
            <span class="text-sm sm:text-base">{{ band.range }}</span>
          </div>
        </div>

        <div :class="animClass('fade-up', 3)" class="mt-10 text-center">
          <MyTextConstructor
            variant="button-lg"
            alignment="center"
            spacing="tight"
            textColor="text-brand-primary"
          >
            <template #myTitle>Aim high — aim for the Hall of Fame</template>
          </MyTextConstructor>
          <div class="mt-4">
            <MyButtonConstructor variant="primary" size="large" @click="showBookingModal = true">
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
