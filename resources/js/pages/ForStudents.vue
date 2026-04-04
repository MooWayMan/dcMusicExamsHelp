<!-- resources/js/pages/ForStudents.vue -->
<script setup lang="ts">
import { usePageAnimation } from '@/composables/usePageAnimation'
import Head from '@/components/layouts/Head.vue'
import Navbar from '@/components/layouts/Navbar.vue'
import Breadcrumbs from '@/components/layouts/Breadcrumbs.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MyFooter from '@/components/layouts/MyFooter.vue'
import { Trophy, Award, Star, Gift } from 'lucide-vue-next'

const { animClass } = usePageAnimation()

const pageMeta = {
  title: 'For Students — musicExams.help',
  description:
    'Your hard work deserves to be celebrated. See how students get recognised through musicExams.help centre code 120 — Hall of Fame, certificates, prize draws and more.',
}

const breadcrumbPages = [
  { name: 'For Students', href: '/for-students', current: true },
]

const bookingUrl = 'https://booking.trinitycollege.com/?larCode=120'

const hallOfFameLogo =
  'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/Highest+score+In5.png'

const tiers = [
  {
    icon: Trophy,
    title: 'Hall of Fame',
    subtitle: 'Quarterly — top scorers only',
    detail: 'The student with the highest Distinction and highest Merit score each quarter gets celebrated on the Hall of Fame. Winners receive a Certificate of Achievement and a gift token.',
    iconBg: 'bg-brand-accent/10',
    iconColor: 'text-brand-accent',
    borderColor: 'border-brand-accent',
  },
  {
    icon: Award,
    title: 'Distinction & Merit certificates',
    subtitle: 'Every high scorer',
    detail: 'Achieve a Distinction (87–100) or Merit (75–86) and you\'ll receive a free digital certificate from musicExams.help celebrating your result. Show it off, share it, put it on the wall.',
    iconBg: 'bg-brand-success-soft',
    iconColor: 'text-brand-success',
    borderColor: 'border-brand-success',
  },
  {
    icon: Star,
    title: 'Thank You page',
    subtitle: 'Every student through centre 120',
    detail: 'Every student who enters an exam through centre 120 gets listed on our Thank You page — your name, instrument and grade for the world to see. Your hard work is always recognised.',
    iconBg: 'bg-brand-teal/10',
    iconColor: 'text-brand-teal',
    borderColor: 'border-brand-teal',
  },
  {
    icon: Gift,
    title: 'Prize draws',
    subtitle: 'Automatic entry',
    detail: 'Every exam entry through centre 120 earns an automatic entry into our quarterly prize draw. Just by sitting your exam, you\'re in with a chance to win.',
    iconBg: 'bg-brand-primary/10',
    iconColor: 'text-brand-primary',
    borderColor: 'border-brand-primary',
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
    <section class="bg-brand-surface pt-24 pb-10 md:pt-28 lg:pt-28">
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
            <p class="mx-auto mt-4 max-w-2xl text-sm text-brand-text-soft sm:text-sm md:text-base lg:text-lg">
              When you sit a Trinity exam through centre 120, your achievement doesn't just disappear into a filing cabinet.
              You get recognised — on our website, with certificates, and through prizes.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- HALL OF FAME BANNER -->
    <section class="bg-black">
      <div class="mx-auto max-w-4xl px-4 py-10 sm:px-6">
        <div :class="animClass('zoom-in', 1)" class="overflow-hidden rounded-2xl bg-black shadow-2xl">
          <img
            :src="hallOfFameLogo"
            alt="Hall of Fame — Highest Score"
            class="h-auto w-full object-contain"
          />
        </div>
      </div>
    </section>

    <!-- RECOGNITION TIERS -->
    <section class="bg-brand-bg">
      <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            class="md:!text-2xl lg:!text-3xl"
          >
            <template #myTitle>How you get recognised</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-2xl text-center text-sm text-brand-text-soft sm:text-sm md:text-base lg:text-lg">
            Every student gets something. The higher you score, the more you earn.
          </p>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-10 space-y-6">
          <div
            v-for="tier in tiers"
            :key="tier.title"
            :class="['flex gap-4 rounded-2xl border-l-4 bg-brand-surface p-5 shadow-sm sm:gap-6 sm:p-6', tier.borderColor]"
          >
            <div :class="[tier.iconBg, 'flex h-10 w-10 shrink-0 items-center justify-center rounded-xl sm:h-12 sm:w-12']">
              <component :is="tier.icon" :class="[tier.iconColor, 'h-5 w-5 sm:h-6 sm:w-6']" />
            </div>
            <div>
              <p class="text-base font-semibold text-brand-text sm:text-lg">{{ tier.title }}</p>
              <p class="text-xs font-medium uppercase tracking-wide text-brand-text-soft">{{ tier.subtitle }}</p>
              <p class="mt-2 text-sm leading-snug text-brand-text-soft sm:text-sm md:text-base">{{ tier.detail }}</p>
            </div>
          </div>
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
          <p class="mx-auto mt-3 max-w-2xl text-center text-sm text-brand-text-soft sm:text-sm md:text-base lg:text-lg">
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
            <a :href="bookingUrl" target="_blank" rel="noopener noreferrer">
              <MyButtonConstructor variant="primary" size="large">
                Book Your Exam
              </MyButtonConstructor>
            </a>
          </div>
        </div>
      </div>
    </section>

    <!-- FOOTER -->
    <MyFooter variant="gradient" />
  </div>
</template>
