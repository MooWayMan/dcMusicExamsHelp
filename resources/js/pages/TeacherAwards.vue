<!-- resources/js/pages/TeacherAwards.vue -->
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
import MyGlassCardConstructor from '@/components/reusables/MyGlassCardConstructor.vue'
import MyFooter from '@/components/layouts/MyFooter.vue'
import { Award, Trophy, Star, Shield, Crown, CheckCircle, Globe } from 'lucide-vue-next'

const { animClass } = usePageAnimation()
const showBookingModal = ref(false)

const pageMeta = {
  title: 'Teacher & School Awards — musicExams.help',
  description:
    'Earn recognition for your exam entries through centre 120. Bronze, Silver, Gold and Top Award tiers for teachers and schools.',
}

const breadcrumbPages = [
  { name: 'For Teachers', href: '/for-teachers', current: false },
  { name: 'Awards', href: '/for-teachers/awards', current: true },
]

const awardTiers = [
  {
    name: 'Bronze Award',
    entries: '10+',
    icon: Shield,
    badge: 'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/awardTA10.png',
    borderColor: 'border-amber-700',
    bgColor: 'bg-amber-700/10',
    textColor: 'text-amber-700',
    ringColor: 'ring-amber-700/30',
    description: 'Your first milestone. Ten entries through centre 120 earns you a Bronze Award badge and certificate. Display it on your website, email signature or social media.',
  },
  {
    name: 'Silver Award',
    entries: '20+',
    icon: Award,
    badge: 'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/awardTA20.png',
    borderColor: 'border-slate-400',
    bgColor: 'bg-slate-400/10',
    textColor: 'text-slate-500',
    ringColor: 'ring-slate-400/30',
    description: 'Twenty entries and counting. The Silver Award shows you are an established route to Trinity exams. A step up from Bronze with its own unique badge.',
  },
  {
    name: 'Gold Award',
    entries: '30+',
    icon: Trophy,
    badge: 'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/awardTA30.png',
    borderColor: 'border-yellow-500',
    bgColor: 'bg-yellow-500/10',
    textColor: 'text-yellow-600',
    ringColor: 'ring-yellow-500/30',
    description: 'Thirty entries is a serious commitment to your students. The Gold Award puts you among the most active teachers and schools using centre 120.',
  },
  {
    name: 'Top Award',
    entries: '40+',
    icon: Crown,
    badge: 'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/awardTA40.png',
    borderColor: 'border-brand-accent',
    bgColor: 'bg-brand-accent/10',
    textColor: 'text-brand-accent',
    ringColor: 'ring-brand-accent/30',
    description: 'The highest tier. Forty or more entries through centre 120 earns you the Top Award — the ultimate recognition for teachers and schools who go above and beyond.',
  },
]

const benefits = [
  'A digital badge to display on your website, social media and email signature',
  'A printable certificate for your studio, classroom or office wall',
  'The right to call yourself an "Award-Winning Music Teacher" or "Award-Winning Music Department"',
  'Every entry counts — regardless of exam result',
  'It doesn\'t matter who books the exam — if the student is yours, it counts',
]

const mockUpCards = [
  {
    icon: Globe,
    title: 'On your website',
    detail: 'Add your badge to your homepage, about page or footer. Parents searching for a music teacher see proof of your track record immediately. Call yourself an "Award-Winning Music Teacher" — because you are.',
  },
  {
    icon: Star,
    title: 'On social media',
    detail: 'Share your achievement on Facebook, Instagram or LinkedIn. Tag your school. Let parents see that your students are entering — and achieving. "Gold Award — 30+ Exam Entries" says it all.',
  },
]

const faqs = [
  {
    id: 'what-counts',
    question: 'What counts as an entry?',
    answer: 'Every student entered through centre 120 counts towards your total — regardless of whether they pass, get a merit, distinction or any other result. The entry itself is what matters. If a parent books the exam directly, just let us know which teacher the student belongs to so we can link it to the right person.',
  },
  {
    id: 'who-books',
    question: 'Does it matter who books the exam?',
    answer: 'No. Parents, teachers and schools can all book exams. As long as we know which teacher or school the student is linked to, it counts. If a parent books directly, either the parent or the teacher can get in touch to let us know — it only takes a moment.',
  },
  {
    id: 'school-or-teacher',
    question: 'Can a school earn an award, or just individual teachers?',
    answer: 'Both. The recognition goes to whoever is named as the applicant on the booking. If a school enters candidates under the school name, the entries count towards the school\'s total. If a teacher enters under their own name, it counts towards theirs. This is worth discussing with your school — if a teacher books on behalf of a school, the entry will be recorded against the teacher\'s name unless the school is listed as the applicant.',
  },
  {
    id: 'display',
    question: 'How do I display my badge?',
    answer: 'You will receive a digital badge image file that you can add to your website, social media profiles, email signature or printed materials. It is yours to use however you like.',
  },
  {
    id: 'how-tracked',
    question: 'How are my entries tracked?',
    answer: 'We track entries through Trinity\'s centre 120 booking records. You do not need to do anything extra — just make sure exams are booked through centre code 120.',
  },
  {
    id: 'cumulative',
    question: 'Do entries reset or carry over?',
    answer: 'Entries reset each quarter. This means you can earn a new award every quarter — so a teacher or school could receive the Top Award in Q1, Q2, Q3 and Q4 of the same year. The more quarters you hit a tier, the more awards you collect.',
  },
]
</script>

<template>
  <Head :title="pageMeta.title" :description="pageMeta.description" />

  <div class="min-h-screen text-brand-text">
    <Navbar />

    <!-- HEADER — white, matching For Teachers page -->
    <section class="bg-white pt-36 pb-10 md:pt-40 lg:pt-40">
      <div class="mx-auto max-w-4xl px-4 sm:px-6">
        <div class="mb-6">
          <Breadcrumbs :pages="breadcrumbPages" home-href="/" />
        </div>
        <div class="text-center">
          <div :class="animClass('fade-up', 0)">
            <MyTextConstructor variant="eyebrow" alignment="center" spacing="tight">
              <template #myTitle>For Teachers & Schools</template>
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
              <template #myTitle>Every entry earns recognition</template>
            </MyTextConstructor>
          </div>

          <div :class="animClass('fade-up', 2)">
            <p class="mx-auto mt-4 max-w-2xl text-base text-brand-text-soft sm:text-base md:text-lg lg:text-xl">
              Enter students through centre 120 and build towards your award.
              It does not matter whether they pass or fail — every single entry counts.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- AWARD TIERS -->
    <section
      class="relative overflow-hidden border-t border-white/10"
      style="background-image: url('https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/blue_BG_9.jpg'); background-size: cover; background-position: center;"
    >
      <div class="absolute inset-0 bg-brand-primary/50"></div>
      <!-- Shooting stars -->
      <div class="shooting-star shooting-star-1"></div>
      <div class="shooting-star shooting-star-2"></div>
      <div class="shooting-star shooting-star-5"></div>
      <div class="shooting-star shooting-star-9"></div>
      <div class="shooting-star shooting-star-3"></div>
      <div class="shooting-star shooting-star-7"></div>

      <div class="relative mx-auto max-w-4xl px-4 py-14 sm:px-6 lg:py-20">
        <div :class="animClass('fade-up', 1)" class="text-center">
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            textColor="text-white"
            class="md:!text-2xl lg:!text-3xl"
          >
            <template #myTitle>Four tiers. One goal.</template>
          </MyTextConstructor>

          <p class="mx-auto mt-3 max-w-2xl text-center text-base text-white/70 sm:text-base md:text-lg lg:text-xl">
            The more students you enter, the higher your award. Totals refresh every quarter. Every entry through centre 120 counts — regardless of result. It doesn't matter who books the exam — as long as we know the student is yours, it counts towards your total. Display your badge with pride.
          </p>
        </div>

        <!-- Tier cards -->
        <div :class="animClass('fade-up', 2)" class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2">
          <div
            v-for="tier in awardTiers"
            :key="tier.name"
            class="flex h-full flex-col overflow-hidden rounded-2xl border-4 bg-white/10 shadow-2xl backdrop-blur-sm"
            :class="[tier.borderColor]"
          >
            <!-- Header — black bar with icon + tier name -->
            <div class="flex w-full items-center gap-3 bg-black px-5 py-3 sm:px-6">
              <component :is="tier.icon" class="h-5 w-5 shrink-0 sm:h-6 sm:w-6" :class="tier.textColor" />
              <div>
                <p class="text-base font-semibold text-white sm:text-lg">{{ tier.name }}</p>
                <p class="text-xs font-medium uppercase tracking-wide" :class="tier.textColor">{{ tier.entries }} entries</p>
              </div>
            </div>

            <!-- Body — description -->
            <div class="flex-1 p-5 sm:p-6">
              <p class="text-base leading-snug text-white/80 sm:text-base md:text-lg">{{ tier.description }}</p>
            </div>

            <!-- Footer — black bar with entries pill -->
            <div class="flex items-center justify-center bg-black px-5 py-3">
              <span
                class="inline-block rounded-full px-4 py-1.5 text-sm font-bold ring-1"
                :class="[tier.bgColor, tier.textColor, tier.ringColor]"
              >
                {{ tier.entries }} entries
              </span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- CERTIFICATE PREVIEW -->
    <section
      class="relative border-t border-white/10"
      style="background-image: url('https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/blue_BG_5.jpg'); background-size: cover; background-position: center;"
    >
      <div class="absolute inset-0 bg-brand-primary/20"></div>
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
            <template #myTitle>Your badge. Your certificate.</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-2xl text-base text-white/80 sm:text-base md:text-lg lg:text-xl">
            Every tier comes with a digital badge and a printable certificate personalised to you or your school.
          </p>
        </div>

        <div :class="animClass('zoom-in', 2)" class="mx-auto mt-8 grid max-w-2xl grid-cols-2 gap-4 sm:grid-cols-4">
          <div
            v-for="tier in awardTiers"
            :key="tier.name"
            class="overflow-hidden rounded-2xl bg-white/10 p-2 shadow-2xl backdrop-blur-sm"
          >
            <img
              :src="tier.badge"
              :alt="tier.name + ' badge'"
              class="block w-full object-contain"
            />
          </div>
        </div>
      </div>
    </section>

    <!-- WHAT YOU GET -->
    <section
      class="relative border-t border-white/10"
      style="background-image: url('https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/blue_BG_6.jpg'); background-size: cover; background-position: center;"
    >
      <div class="absolute inset-0 bg-brand-primary/50"></div>
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
            <template #myTitle>What you get</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 2)" class="mx-auto mt-8 max-w-2xl space-y-4">
          <div
            v-for="(benefit, index) in benefits"
            :key="index"
            class="flex items-start gap-3 rounded-xl bg-white/10 p-4 backdrop-blur-sm"
          >
            <CheckCircle class="mt-0.5 h-5 w-5 shrink-0 text-brand-accent" />
            <p class="text-base text-white/80 sm:text-base md:text-lg">{{ benefit }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- MOCK-UP SECTION — how badges look in the wild -->
    <section
      class="relative border-t border-white/10"
      style="background-image: url('https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/blue_BG_9.jpg'); background-size: cover; background-position: center;"
    >
      <div class="absolute inset-0 bg-black/50"></div>
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
            <template #myTitle>Imagine this on your website</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-2xl text-center text-base text-white/80 sm:text-base md:text-lg lg:text-xl">
            A badge on your homepage. "Award-Winning Music Teacher" in your bio.
            Parents notice these things — it builds trust before they even pick up the phone.
          </p>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-8">
          <MyGlassCardConstructor :cards="mockUpCards" :columns="2" />
        </div>
      </div>
    </section>

    <!-- FAQ -->
    <section
      class="relative border-t border-white/10"
      style="background-image: url('https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/blue_BG_5.jpg'); background-size: cover; background-position: center;"
    >
      <div class="absolute inset-0 bg-brand-primary/20"></div>
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
      </div>
    </section>

    <!-- CTA -->
    <section
      class="relative border-t border-white/10"
      style="background-image: url('https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/blue_BG_6.jpg'); background-size: cover; background-position: center;"
    >
      <div class="absolute inset-0 bg-brand-primary/50"></div>
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
            <template #myTitle>Start building towards your award today</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-xl text-base text-white/80 sm:text-base md:text-lg lg:text-xl">
            Every exam booked through centre 120 counts. Your next entry could be the one that tips you into a new tier.
          </p>
          <div class="mt-6">
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
