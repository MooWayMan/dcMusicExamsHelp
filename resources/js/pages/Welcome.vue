<!-- resources/js/pages/Welcome.vue -->
<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import Head from '@/components/layouts/Head.vue'
import Navbar from '@/components/layouts/Navbar.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MyRunnerListTextInfo from '@/components/reusables/MyRunnerListTextInfo.vue'
import MyAccordionConstructor from '@/components/reusables/MyAccordionConstructor.vue'

const pageMeta = {
  title: 'musicexams.help',
  description:
    'Simple music exam guidance from Trinity Registered Exam Centre 120.',
}

const referralCode = '120'
const bookingUrl = 'https://booking.trinitycollege.com/?larCode=120'

const heroLogo =
  'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/logo.png'

const trinityCentreLogo =
  'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/Trinity_Centre_120_logo_purple.png'

const whyCards = [
  {
    id: 1,
    title: 'Clear booking path',
    subTitle: 'A simpler route through the process, without extra clutter.',
    showIcon: false,
  },
  {
    id: 2,
    title: 'Code 120 kept visible',
    subTitle: 'The centre code stays prominent so it is easier to remember at the right time.',
    showIcon: false,
  },
  {
    id: 3,
    title: 'Support, not competition',
    subTitle: 'Designed to help teachers, parents and students while keeping teachers central.',
    showIcon: false,
  },
]

const incentives = [
  'Quarterly prize draws for entries using code 120',
  'Recognition for teachers and students',
  'Hall of Fame features, shout-outs and more',
]

const faqs = [
  {
    question: 'Do I book through this website?',
    answer:
      'No. This site gives guidance and then points you to the official booking route.',
  },
  {
    question: 'Who is this for?',
    answer:
      'Teachers, parents and students who want a simpler route through music exam booking.',
  },
  {
    question: 'Is this only for Trinity?',
    answer:
      'Trinity is the current focus, but the wider musicexams.help brand leaves room for other exam boards later.',
  },
]

const usefulLinks = [
  {
    id: 1,
    title: 'Go to official booking',
    subTitle: 'Continue to the Trinity booking route when you are ready.',
    url: bookingUrl,
  },
  {
    id: 2,
    title: 'Why use code 120',
    subTitle: 'See why keeping the centre code visible matters before booking.',
    url: '#why',
  },
  {
    id: 3,
    title: 'Incentives and recognition',
    subTitle: 'See how code 120 may later connect to draws, recognition and Hall of Fame features.',
    url: '#incentives',
  },
]

const handleRunnerClick = (card: { url?: string; isExternal?: boolean }) => {
  if (!card.url) return

  if (card.url.startsWith('#')) {
    const element = document.querySelector(card.url)
    element?.scrollIntoView({ behavior: 'smooth' })
    return
  }

  if (card.isExternal) {
    window.open(card.url, '_blank', 'noopener,noreferrer')
    return
  }

  router.visit(card.url)
}
</script>

<template>
  <Head :title="pageMeta.title" :description="pageMeta.description" />

  <div class="min-h-screen bg-[#f7f8fb] text-slate-900">
    <Navbar />

    <!-- HERO -->
    <section class="relative flex justify-center bg-white">
      <div class="mx-auto flex w-full max-w-6xl flex-col items-center px-4 pt-14 pb-10 text-center sm:px-6 md:pt-20 lg:pt-20">
        <img
          :src="heroLogo"
          alt="musicexams.help"
          class="h-44 w-auto sm:h-56 md:h-72 lg:h-80 xl:h-[26rem]"
        />

        <MyTextConstructor
          variant="display"
          alignment="center"
          fontFamily="display"
          spacing="tight"
          class="mt-6 max-w-4xl"
        >
          <template #myTitle>
            Music exam booking made clearer
          </template>
        </MyTextConstructor>

        <MyTextConstructor
          alignment="center"
          bodyVariant="body"
          spacing="none"
          class="mt-4 max-w-3xl text-slate-700"
        >
          Simple guidance for teachers, parents and students. Keep
          <span class="font-semibold text-slate-950">code {{ referralCode }}</span>
          ready before booking.
        </MyTextConstructor>

        <div class="mt-6 flex flex-wrap items-center justify-center gap-4">
          <a
            :href="bookingUrl"
            target="_blank"
            rel="noopener noreferrer"
          >
            <MyButtonConstructor variant="primary" size="large">
              Book Your Exam
            </MyButtonConstructor>
          </a>

          <a href="#why">
            <MyButtonConstructor variant="outline" size="large">
              Why use this page
            </MyButtonConstructor>
          </a>
        </div>
      </div>
    </section>

    <!-- TRUST STRIP -->
    <div class="w-full border-y border-slate-200 bg-slate-50 py-6 md:py-12">
      <div class="mx-auto max-w-5xl px-6 py-5">
        <div class="flex flex-col items-center gap-6 sm:flex-row sm:justify-between">
          <MyTextConstructor
            alignment="left"
            bodyVariant="body"
            spacing="none"
            class="text-center sm:text-left text-slate-700"
          >
            Run independently with guidance built around
            <span class="font-semibold text-slate-950">
              Trinity Registered Exam Centre 120
            </span>
          </MyTextConstructor>

          <img
            :src="trinityCentreLogo"
            alt="Trinity Registered Exam Centre 120"
            class="h-24 w-auto sm:h-28 md:h-32 lg:h-36 xl:h-40"
          />
        </div>
      </div>
    </div>

    <!-- WHY -->
    <section id="why" class="border-b border-slate-200 bg-white">
      <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8 lg:py-16">
        <div class="max-w-3xl">
          <MyTextConstructor variant="eyebrow" spacing="tight">
            <template #myTitle>
              Why use this page
            </template>
          </MyTextConstructor>

          <MyTextConstructor
            variant="heading"
            fontFamily="display"
            spacing="tight"
            class="mt-3"
          >
            <template #myTitle>
              Built to save time and reduce confusion
            </template>
          </MyTextConstructor>

          <MyTextConstructor
            bodyVariant="body"
            spacing="none"
            class="mt-4 max-w-2xl text-slate-700"
          >
            The goal is simple: make music exam booking easier to understand, keep code
            {{ referralCode }} visible, and guide people to the official booking route with more confidence.
          </MyTextConstructor>
        </div>

        <div class="mt-10 grid gap-5 md:grid-cols-3">
          <div
            v-for="card in whyCards"
            :key="card.id"
            class="rounded-2xl border-[6px] border-[#1e3a8a] bg-gradient-to-br from-[#dbeafe] via-[#eff6ff] to-[#bfdbfe] p-6 shadow-sm transition-all duration-200 hover:-translate-y-1 hover:shadow-md"
          >
            <MyTextConstructor
              variant="subheading"
              spacing="tight"
              textColor="text-blue-900"
            >
              <template #myTitle>
                {{ card.title }}
              </template>
            </MyTextConstructor>

            <MyTextConstructor
              subTitleVariant="muted"
              spacing="none"
              textColor="text-blue-950/80"
              class="mt-2"
            >
              <template #mySubTitle>
                {{ card.subTitle }}
              </template>
            </MyTextConstructor>
          </div>
        </div>
      </div>
    </section>

    <!-- INCENTIVES -->
    <section id="incentives" class="border-t border-slate-200 bg-[#f7f8fb]">
      <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
        <!-- TOP ROW -->
        <div class="grid gap-10 lg:grid-cols-2 lg:gap-12">
          <!-- LEFT -->
          <div>
            <MyTextConstructor variant="eyebrow" spacing="tight">
              <template #myTitle>
                Incentives
              </template>
            </MyTextConstructor>

            <MyTextConstructor
              variant="heading"
              fontFamily="display"
              spacing="tight"
              class="mt-3 max-w-2xl"
            >
              <template #myTitle>
                Your students work hard. So do you.
              </template>
            </MyTextConstructor>

            <MyTextConstructor
              bodyVariant="body"
              spacing="none"
              class="mt-5 max-w-2xl text-slate-700"
            >
              Students put in the practice. Teachers put in the time. Code {{ referralCode }}
              is designed to do more than help with booking — it can support quarterly
              prize draws, recognition, shout-outs and future rewards.
            </MyTextConstructor>
          </div>

          <!-- RIGHT -->
          <div class="lg:pt-12">
            <div class="space-y-4">
              <div
                v-for="item in incentives"
                :key="item"
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
              >
                <MyTextConstructor bodyVariant="body" spacing="none">
                  {{ item }}
                </MyTextConstructor>
              </div>
            </div>
          </div>
        </div>

        <!-- BOTTOM ROW -->
        <div class="mt-10">
          <div class="mx-auto max-w-7xl">
            <div class="overflow-hidden rounded-[2rem] bg-white p-3 shadow-xl ring-1 ring-slate-200">
              <img
                src="/images/rewards_add.png"
                alt="Trinity exam rewards and recognition using code 120"
                class="block w-full rounded-[1.5rem]"
              />
            </div>

            <div class="mt-6">
              <a
                :href="bookingUrl"
                target="_blank"
                rel="noopener noreferrer"
                class="block"
              >
                <MyButtonConstructor variant="primary" size="large" fullWidth>
                  Continue to Official Booking
                </MyButtonConstructor>
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- USEFUL LINKS -->
    <section class="border-t border-slate-200 bg-white">
      <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8 lg:py-16">
        <MyTextConstructor
          variant="heading"
          fontFamily="display"
          alignment="center"
          spacing="tight"
        >
          <template #myTitle>
            Useful next steps
          </template>
        </MyTextConstructor>

        <MyTextConstructor
          subTitleVariant="muted"
          alignment="center"
          spacing="none"
          class="mt-3"
        >
          <template #mySubTitle>
            Quick routes to the most useful parts of this page and the official booking process.
          </template>
        </MyTextConstructor>

        <div class="mt-8">
          <MyRunnerListTextInfo
            :theArray="usefulLinks"
            maxWidth="4xl"
            @cardClick="handleRunnerClick"
          />
        </div>
      </div>
    </section>

    <!-- FAQ -->
    <section id="faq" class="border-t border-slate-200 bg-white">
      <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8 lg:py-16">
        <div class="max-w-3xl">
          <MyTextConstructor variant="eyebrow" spacing="tight">
            <template #myTitle>
              FAQ
            </template>
          </MyTextConstructor>

          <MyTextConstructor
            variant="heading"
            fontFamily="display"
            spacing="tight"
            class="mt-3"
          >
            <template #myTitle>
              Common questions
            </template>
          </MyTextConstructor>
        </div>

        <div class="mt-10">
          <MyAccordionConstructor
            :items="faqs.map((faq, index) => ({
              id: index + 1,
              question: faq.question,
              answer: faq.answer,
            }))"
            size="medium"
            header-bg-color="bg-white"
            header-text-color="text-brand-primary"
            header-hover-bg-color="hover:bg-brand-bg"
            border-color="border-brand-border"
            content-bg-color="bg-brand-surface"
          />
        </div>
      </div>
    </section>
  </div>
</template>