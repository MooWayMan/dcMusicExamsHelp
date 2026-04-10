<!-- resources/js/pages/ForStudents.vue -->
<script setup lang="ts">
import { usePageAnimation } from '@/composables/usePageAnimation'
import { useBookingModal } from '@/composables/useBookingModal'
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
const { showBookingModal } = useBookingModal()

const pageMeta = {
  title: 'For Students — musicExams.help',
  description:
    'Your hard work deserves to be celebrated. See how students get recognised through musicExams.help centre 120 — Hall of Fame, certificates, prize draws and more.',
}

const breadcrumbPages = [
  { name: 'For Students', href: '/for-students', current: true },
]

const s3Base = 'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/'

const hallOfFameLogo = `${s3Base}Highest+score+In5.png`

const studentCertificates = [
  { name: 'Bravo Certificate', image: `${s3Base}certStu_1.png` },
  { name: 'Take a Bow Certificate', image: `${s3Base}certStu_2.png` },
  { name: 'Standing Ovation Certificate', image: `${s3Base}certStu_3.png` },
  { name: 'Centre Stage Certificate', image: `${s3Base}certStu_4.png` },
  { name: 'Showstopper Certificate', image: `${s3Base}certStu_5.png` },
]

const tiers = [
  {
    icon: Trophy,
    title: 'Top Scorers',
    subtitle: 'Quarterly — the very best',
    detail: 'The highest Distinction each quarter earns a <strong>Showstopper Certificate</strong>. The highest Merit earns a <strong>Centre Stage Certificate</strong>. Both earn the top spot on the <strong>Hall of Fame</strong> — plus a gift token (£20, or divided equally if there is a tie — minimum £5 each). This is the ultimate recognition for outstanding achievement.',
    link: '/recognition?from=for-students',
    linkText: 'See the Hall of Fame',
  },
  {
    icon: Award,
    title: 'Hall of Fame',
    subtitle: 'Every Merit and Distinction',
    detail: 'Score a Merit and earn a Take a Bow Certificate. Score a Distinction and earn a Standing Ovation Certificate. Every Merit and Distinction student gets celebrated in the <strong>Hall of Fame</strong> — show it off, share it, put it on the wall.',
    link: '/recognition?from=for-students',
    linkText: 'See the Hall of Fame',
  },
  {
    icon: Star,
    title: 'Recognition page',
    subtitle: 'Every student through centre 120',
    detail: 'Every student who enters an exam through centre 120 — whether face-to-face, digital or theory — receives at least a <strong>Bravo Certificate</strong> and gets listed on our Recognition page. Your first name and initial, instrument and grade. This can later be changed to show your full name with permission. Your hard work is always recognised.',
    link: '/recognition?from=for-students',
    linkText: 'Visit the Recognition page',
  },
  {
    icon: Gift,
    title: 'Prize draws',
    subtitle: 'Automatic entry',
    detail: 'Every exam entry through centre 120 — face-to-face, digital or theory — earns an automatic entry into our quarterly prize draw. Just by sitting your exam, you\'re in with a chance to win a gift token!',
    link: '/incentives?from=for-students',
    linkText: 'See all incentives',
  },
]

const resultsBands = [
  { label: 'Distinction', range: '87–100', color: 'bg-brand-accent', textColor: 'text-white', cert: 'You will receive a Standing Ovation Certificate' },
  { label: 'Merit', range: '75–86', color: 'bg-brand-success', textColor: 'text-white', cert: 'You will receive a Take a Bow Certificate' },
  { label: 'Pass', range: '60–74', color: 'bg-brand-teal', textColor: 'text-white', cert: 'You will receive a Bravo Certificate' },
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
        <a href="/recognition?from=for-students" :class="animClass('zoom-in', 1)" class="group block overflow-hidden rounded-2xl bg-black shadow-2xl transition-transform duration-300 hover:scale-[1.005]">
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

        <!-- CTA to Recognition page -->
        <div :class="animClass('fade-up', 3)" class="mt-10 text-center">
          <a href="/recognition?from=for-students">
            <MyButtonConstructor variant="light" size="large">
              Visit the Recognition Page
            </MyButtonConstructor>
          </a>
        </div>
      </div>
    </section>

    <!-- CERTIFICATE SUITE + MOCKUP -->
    <section
      class="relative border-t border-white/10"
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
            <template #myTitle>The full certificate suite</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-2xl text-base text-white/80 sm:text-base md:text-lg lg:text-xl">
            Every student gets at least a Bravo. Score higher and earn something even better.
          </p>
        </div>

        <!-- Student certificates -->
        <div :class="animClass('zoom-in', 2)" class="mx-auto mt-8 flex max-w-3xl flex-wrap items-start justify-center gap-4">
          <div
            v-for="cert in studentCertificates"
            :key="cert.name"
            class="w-[calc(50%-0.5rem)] overflow-hidden rounded-2xl bg-white/10 p-2 shadow-2xl backdrop-blur-sm sm:w-[calc(33.333%-0.75rem)] lg:w-[calc(20%-0.8rem)]"
          >
            <img
              :src="cert.image"
              :alt="cert.name"
              class="block w-full object-contain"
            />
            <p class="mt-2 text-center text-sm font-semibold text-white/80">{{ cert.name }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Living room mockup — full width, cropped to remove Canva white border -->
    <section class="bg-black">
      <div :class="animClass('zoom-in', 3)">
        <img
          src="https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/mockLivingRommCerts.png"
          alt="musicExams.help certificates framed on a living room wall"
          class="h-auto w-full object-cover"
        />
      </div>
      <div class="mx-auto max-w-4xl px-4 pb-10 pt-4 sm:px-6">
        <MyTextConstructor variant="muted" alignment="center" textColor="text-white/80" spacing="none">
          Your certificates deserve a spot on the wall — frame them, share them, celebrate them.
        </MyTextConstructor>
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

        <div :class="animClass('fade-up', 2)" class="mx-auto mt-8 max-w-md space-y-3">
          <div
            v-for="band in resultsBands"
            :key="band.label"
            :class="[band.color, band.textColor, 'rounded-xl px-5 py-3 shadow-sm']"
          >
            <div class="flex items-center justify-between font-semibold">
              <span class="text-base sm:text-lg">{{ band.label }}</span>
              <span class="text-sm sm:text-base">{{ band.range }}</span>
            </div>
            <p class="mt-1 text-sm text-white/80 sm:text-base">{{ band.cert }}</p>
          </div>
        </div>

        <p class="mx-auto mt-6 max-w-lg text-center text-sm text-brand-text-soft sm:text-base">
          These are in addition to the official Trinity certificate you receive automatically for any Pass, Merit or Distinction.
        </p>

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
