<!-- resources/js/pages/ThankYou.vue -->
<script setup lang="ts">
import { ref } from 'vue'
import { usePageAnimation } from '@/composables/usePageAnimation'
import Head from '@/components/layouts/Head.vue'
import Navbar from '@/components/layouts/Navbar.vue'
import Breadcrumbs from '@/components/layouts/Breadcrumbs.vue'
import BookingModal from '@/components/BookingModal.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MyFooter from '@/components/layouts/MyFooter.vue'
import { Heart, Trophy, Music, Star, Award, Gift } from 'lucide-vue-next'

const { animClass } = usePageAnimation()
const showBookingModal = ref(false)

const pageMeta = {
  title: 'Thank You — musicExams.help',
  description:
    'Every student who enters a Trinity exam through centre 120 gets recognised here. Thank you for your hard work.',
}

const breadcrumbPages = [
  { name: 'Thank You', href: '/thank-you', current: true },
]

const hallOfFameLogo =
  'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/Highest+score+In5.png'

const certStudent =
  'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/certAceiveStu_Dis.png'

const thankYouHero =
  'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/Thank_You_card.png'

/* ── Mock data — will be replaced with real database entries ── */
const currentQuarter = 'Q1 2026'

const hallOfFameEntries = [
  {
    name: 'Sophie L',
    instrument: 'Piano',
    grade: 'Grade 5',
    score: 96,
    result: 'Distinction',
    award: 'Highest Distinction',
    location: 'Liverpool, England',
  },
  {
    name: 'James T',
    instrument: 'Trumpet',
    grade: 'Grade 3',
    score: 84,
    result: 'Merit',
    award: 'Highest Merit',
    location: 'Glasgow, Scotland',
  },
]

const thankYouEntries = [
  { name: 'Sophie L', instrument: 'Piano', grade: 'Grade 5', result: 'Distinction', location: 'Liverpool, England' },
  { name: 'Amara K', instrument: 'Singing', grade: 'Grade 4', result: 'Distinction', location: 'Manchester, England' },
  { name: 'James T', instrument: 'Trumpet', grade: 'Grade 3', result: 'Merit', location: 'Glasgow, Scotland' },
  { name: 'Freddie B', instrument: 'Guitar', grade: 'Grade 2', result: 'Merit', location: 'Liverpool, England' },
  { name: 'Isla M', instrument: 'Clarinet', grade: 'Grade 6', result: 'Merit', location: 'Edinburgh, Scotland' },
  { name: 'Noah P', instrument: 'Piano', grade: 'Grade 1', location: 'Cardiff, Wales' },
  { name: 'Emily R', instrument: 'Flute', grade: 'Grade 3' },
  { name: 'Oliver S', instrument: 'Drums', grade: 'Grade 2', location: 'Birmingham, England' },
  { name: 'Mia W', instrument: 'Violin', grade: 'Grade 4' },
  { name: 'Archie D', instrument: 'Saxophone', grade: 'Grade 1', location: 'Belfast, Northern Ireland' },
]

const resultBadgeClass = (result: string) => {
  switch (result) {
    case 'Distinction':
      return 'bg-brand-accent text-white'
    case 'Merit':
      return 'bg-brand-success text-white'
    case 'Pass':
      return 'bg-brand-teal text-white'
    default:
      return 'bg-brand-surface-soft text-brand-text'
  }
}

const certificateName = (result?: string) => {
  switch (result) {
    case 'Distinction':
      return 'Standing Ovation Certificate'
    case 'Merit':
      return 'Take a Bow Certificate'
    default:
      return null
  }
}
</script>

<template>
  <Head :title="pageMeta.title" :description="pageMeta.description" />

  <div class="min-h-screen bg-brand-bg text-brand-text">
    <Navbar />

    <!-- HEADER — Canva hero card on dark background -->
    <section class="bg-brand-bg pt-24 pb-2 md:pt-28 lg:pt-28">
      <div class="mx-auto max-w-4xl px-4 sm:px-6">
        <div class="mb-4">
          <Breadcrumbs :pages="breadcrumbPages" home-href="/" />
        </div>
        <div :class="animClass('zoom-in', 1)" class="overflow-hidden rounded-2xl shadow-2xl">
          <img
            :src="thankYouHero"
            alt="Thank You — Every student. Every exam. Recognised. Centre 120"
            class="h-auto w-full object-contain"
          />
        </div>
        <div :class="animClass('fade-up', 2)" class="mt-4 text-center">
          <span class="inline-flex items-center gap-2 rounded-full bg-brand-accent/10 px-4 py-2 ring-1 ring-brand-accent/20">
            <Heart class="h-4 w-4 text-brand-accent" />
            <span class="text-sm font-semibold text-brand-accent">{{ currentQuarter }}</span>
          </span>
        </div>
      </div>
    </section>

    <!-- HALL OF FAME SPOTLIGHT — star constellation background -->
    <section
      class="relative border-t border-white/10"
      style="background-image: url('https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/blue_stars_bg.jpg'); background-size: cover; background-position: center;"
    >
      <div class="absolute inset-0 bg-black/50"></div>

      <!-- Shooting stars -->
      <div class="pointer-events-none absolute inset-0 overflow-hidden">
        <div class="shooting-star shooting-star-1"></div>
        <div class="shooting-star shooting-star-2"></div>
        <div class="shooting-star shooting-star-5"></div>
        <div class="shooting-star shooting-star-9"></div>
        <div class="shooting-star shooting-star-3"></div>
        <div class="shooting-star shooting-star-7"></div>
      </div>

      <div class="relative mx-auto max-w-4xl px-4 py-14 sm:px-6 lg:py-20">
        <!-- Hall of Fame banner -->
        <div :class="animClass('zoom-in', 1)" class="overflow-hidden rounded-2xl shadow-2xl">
          <img
            :src="hallOfFameLogo"
            alt="Hall of Fame — Highest Score"
            class="h-auto w-full object-contain"
          />
        </div>

        <!-- Quarter label -->
        <div :class="animClass('fade-up', 2)" class="mt-8 text-center">
          <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-5 py-2 backdrop-blur-sm ring-1 ring-white/20">
            <Trophy class="h-4 w-4 text-brand-accent" />
            <span class="text-sm font-semibold text-white">{{ currentQuarter }} — Top Scorers</span>
          </span>
        </div>

        <!-- Hall of Fame winner cards — spotlight style -->
        <div :class="animClass('fade-up', 3)" class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2">
          <div
            v-for="entry in hallOfFameEntries"
            :key="entry.name + entry.award"
            class="relative overflow-hidden rounded-2xl border-4 border-yellow-400 bg-white/10 p-6 shadow-2xl shadow-yellow-400/20 ring-2 ring-yellow-300/40 backdrop-blur-sm"
          >
            <!-- Decorative trophy -->
            <div class="absolute right-4 top-4">
              <Trophy class="h-10 w-10 text-brand-accent/30" />
            </div>

            <!-- Award label -->
            <div class="flex items-center gap-2">
              <Star class="h-4 w-4 text-brand-accent" />
              <p class="text-xs font-bold uppercase tracking-widest text-brand-accent">{{ entry.award }}</p>
            </div>

            <!-- Student name — big and bold -->
            <p class="mt-3 text-2xl font-extrabold text-white sm:text-3xl">{{ entry.name }}</p>

            <!-- Instrument, grade and location -->
            <p class="mt-1 text-base text-white/70 sm:text-base">
              {{ entry.instrument }} · {{ entry.grade }}
            </p>
            <p v-if="entry.location" class="mt-1 text-sm text-white/50">
              {{ entry.location }}
            </p>

            <!-- Score badge -->
            <div class="mt-4 flex items-center gap-3">
              <span :class="[resultBadgeClass(entry.result), 'rounded-full px-4 py-1.5 text-sm font-bold shadow-lg']">
                {{ entry.result }} — {{ entry.score }}
              </span>
            </div>

            <!-- Certificate earned -->
            <p v-if="certificateName(entry.result)" class="mt-3 text-xs font-semibold text-white/60">
              <Award class="mb-0.5 mr-1 inline h-3.5 w-3.5 text-brand-accent" />{{ certificateName(entry.result) }}
            </p>

            <!-- Gift token callout -->
            <div class="mt-4 flex items-center gap-2 rounded-lg bg-brand-accent/20 px-3 py-2">
              <Gift class="h-4 w-4 text-brand-accent" />
              <span class="text-xs font-semibold text-white">Gift token winner</span>
            </div>
          </div>
        </div>

        <!-- EVERY ENTRY COUNTS — student table on same star background -->
        <div class="mt-14">
          <div :class="animClass('fade-up', 4)">
            <MyTextConstructor
              variant="subheading"
              fontFamily="display"
              alignment="center"
              spacing="tight"
              textColor="text-white"
              class="md:!text-2xl lg:!text-3xl"
            >
              <template #myTitle>Every entry counts</template>
            </MyTextConstructor>
            <p class="mx-auto mt-3 max-w-2xl text-center text-base text-white/70 sm:text-base md:text-lg lg:text-xl">
              Every student who enters through centre 120 is a star to us. You sat the exam, you did the work,
              and your name belongs here.
            </p>
          </div>

          <!-- Student list -->
          <div :class="animClass('fade-up', 5)" class="mt-8">
            <div class="overflow-hidden rounded-2xl shadow-2xl ring-1 ring-white/20 backdrop-blur-md">
              <!-- Table header -->
              <div class="bg-white/25 px-4 py-3 backdrop-blur-md sm:px-6">
                <div class="grid grid-cols-12 gap-2">
                  <div class="col-span-5 sm:col-span-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-white sm:text-sm">Student</p>
                  </div>
                  <div class="col-span-4 sm:col-span-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-white sm:text-sm">Instrument</p>
                  </div>
                  <div class="col-span-3 sm:col-span-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-white sm:text-sm">Grade</p>
                  </div>
                </div>
              </div>

              <!-- Table rows -->
              <div class="divide-y divide-white/10">
                <div
                  v-for="(entry, index) in thankYouEntries"
                  :key="entry.name + entry.instrument"
                  class="grid grid-cols-12 items-center gap-2 px-4 py-3 sm:px-6"
                  :class="index % 2 === 1 ? 'bg-white/15' : 'bg-white/10'"
                >
                  <div class="col-span-5 sm:col-span-4">
                    <div class="flex items-center gap-2">
                      <Music class="hidden h-4 w-4 shrink-0 text-brand-accent sm:block" />
                      <p class="text-base font-semibold text-white sm:text-base">{{ entry.name }}</p>
                    </div>
                    <p v-if="entry.location" class="mt-0.5 text-[10px] text-white/40 sm:text-xs sm:ml-6">
                      {{ entry.location }}
                    </p>
                    <p v-if="entry.result && certificateName(entry.result)" class="mt-0.5 text-[10px] font-medium text-brand-accent sm:text-xs sm:ml-6">
                      <Award class="mb-0.5 mr-0.5 inline h-3 w-3" />{{ certificateName(entry.result) }}
                    </p>
                  </div>
                  <div class="col-span-4 sm:col-span-4">
                    <p class="text-base text-white/70 sm:text-base">{{ entry.instrument }}</p>
                  </div>
                  <div class="col-span-3 sm:col-span-4">
                    <p class="text-base text-white/70 sm:text-base">{{ entry.grade }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Sample data notice -->
            <div class="mt-4 rounded-xl bg-white/5 px-4 py-3 text-center ring-1 ring-white/10">
              <p class="text-xs text-white/50 italic sm:text-sm">
                Sample entries shown — real student results will appear here once exams are entered through centre 120
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- CERTIFICATE PREVIEW — moved below the leaderboard -->
    <section class="bg-brand-bg">
      <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)" class="mx-auto max-w-xl space-y-3">
          <div class="flex items-center gap-3 rounded-xl bg-brand-surface p-4 shadow-sm border-4 border-brand-accent">
            <Award class="h-6 w-6 shrink-0 text-brand-success" />
            <p class="text-base font-semibold text-brand-text sm:text-base md:text-lg">Do well and earn a <span class="text-brand-success">Take a Bow Certificate</span></p>
          </div>
          <div class="flex items-center gap-3 rounded-xl bg-brand-surface p-4 shadow-sm border-4 border-brand-accent">
            <Trophy class="h-6 w-6 shrink-0 text-brand-accent" />
            <p class="text-base font-semibold text-brand-text sm:text-base md:text-lg">Aim high and earn a <span class="text-brand-accent">Standing Ovation Certificate</span></p>
          </div>
        </div>

        <div :class="animClass('zoom-in', 2)" class="mx-auto mt-8 max-w-md">
          <div class="overflow-hidden rounded-2xl bg-brand-surface p-3 shadow-2xl border-4 border-brand-accent">
            <img
              :src="certStudent"
              alt="Standing Ovation Certificate — Distinction"
              class="block w-full rounded-2xl"
            />
          </div>
          <p class="mt-3 text-center text-xs text-brand-text-soft italic">
            Example certificate — your name, instrument, grade and result will appear on yours
          </p>
        </div>
      </div>
    </section>

    <!-- DIVIDER — atmospheric piano keys -->
    <!-- DIVIDER — atmospheric piano keys -->
    <section class="relative bg-brand-bg">
      <div class="mx-auto max-w-5xl px-4 py-2 sm:px-6">
        <div class="relative overflow-hidden rounded-2xl">
          <img
            src="https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/IMG_2226.jpeg"
            alt=""
            class="h-24 w-full object-cover opacity-30 sm:h-32"
          />
          <div class="absolute inset-0 bg-gradient-to-r from-brand-bg via-transparent to-brand-bg"></div>
        </div>
      </div>
    </section>

    <!-- CTA SECTION -->
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
            <template #myTitle>Ready to get your name on the board?</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-xl text-base text-brand-text-soft sm:text-base md:text-lg lg:text-xl">
            Book your Trinity exam through centre 120 and your result will be celebrated here.
          </p>
          <div class="mt-6">
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
