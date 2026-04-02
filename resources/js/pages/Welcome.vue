<!-- resources/js/pages/Welcome.vue -->
<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import Head from '@/components/layouts/Head.vue'
import Navbar from '@/components/layouts/Navbar.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MyRunnerListTextInfo from '@/components/reusables/MyRunnerListTextInfo.vue'
import MyAccordionConstructor from '@/components/reusables/MyAccordionConstructor.vue'
import MyFooter from '@/components/layouts/MyFooter.vue'

const pageMeta = {
  title: 'musicexams.help — Your Guide to Trinity Music Exams',
  description:
    'Free guidance for teachers, parents and students booking Trinity music exams through Centre 120. Incentives, recognition and support.',
}

const referralCode = '120'
const bookingUrl = 'https://booking.trinitycollege.com/?larCode=120'

const heroLogo =
  'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/musicexamshelp_logo2.png'

const trinityCentreLogo =
  'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/Trinity_Centre_120_logo_purple.png'

const whyCards = [
  {
    id: 1,
    title: 'For Teachers',
    subTitle: 'Save time with a clear booking route, get recognised for the work you do, and access exclusive resources and discounts on exam materials.',
    showIcon: false,
  },
  {
    id: 2,
    title: 'For Parents',
    subTitle: 'No more confusion about how exams work. Step-by-step guidance from choosing the right exam to understanding results day.',
    showIcon: false,
  },
  {
    id: 3,
    title: 'For Students',
    subTitle: 'Your hard work deserves to be celebrated. Incentives, prize draws and a Hall of Fame for students who go the distance.',
    showIcon: false,
  },
]

const incentives = [
  {
    title: 'Quarterly prize draws',
    detail: 'Every exam entry through code 120 earns an automatic entry into our quarterly prize draw. The more students you enter, the more chances to win.',
  },
  {
    title: 'Teacher recognition',
    detail: 'Hall of Fame features, shout-outs and certificates for teachers whose students achieve outstanding results.',
  },
  {
    title: 'Access Fund support',
    detail: 'Trinity offers grants of up to £300 per candidate for students facing financial barriers. We help teachers find and apply for funding their students deserve.',
  },
  {
    title: 'Exam book discounts',
    detail: 'Exclusive discounts on Faber Music publications and exam materials, available to teachers booking through centre 120.',
  },
]

const faqs = [
  {
    question: 'Do I book through this website?',
    answer:
      'No — we guide you through the process and then link you directly to the official Trinity booking page. When you get there, centre code 120 will already be applied.',
  },
  {
    question: 'Who is this for?',
    answer:
      'Anyone involved in music exams. Teachers looking for a smoother booking process and recognition. Parents wanting clear guidance on how exams work. Students preparing for their next grade.',
  },
  {
    question: 'What is centre code 120?',
    answer:
      'It is a Trinity College London registered exam centre code. Using code 120 when booking connects your entry to this centre, which unlocks access to incentives, recognition and ongoing support.',
  },
  {
    question: 'Does it cost anything extra?',
    answer:
      'No. The exam fees are the same regardless of which centre code you use. Code 120 simply gives you access to the extra benefits and support offered through musicexams.help.',
  },
  {
    question: 'Is this only for Trinity exams?',
    answer:
      'Trinity College London is the current focus, covering graded music exams across all instruments. The wider musicexams.help brand may expand to include other exam boards in the future.',
  },
  {
    question: 'Can I use this if I already have a teacher?',
    answer:
      'Absolutely. This site supports your existing teacher, not replaces them. Your teacher stays central to everything — we just make the booking and admin side easier for everyone.',
  },
]

const usefulLinks = [
  {
    id: 1,
    title: 'Book your exam now',
    subTitle: 'Ready to go? Head straight to the official Trinity booking page with code 120 already applied.',
    url: bookingUrl,
  },
  {
    id: 2,
    title: 'See how it works',
    subTitle: 'Find out why teachers, parents and students are choosing to book through centre 120.',
    url: '#why',
  },
  {
    id: 3,
    title: 'Explore the incentives',
    subTitle: 'Prize draws, teacher recognition, student funding and discounts on exam materials.',
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

  <div class="min-h-screen bg-brand-bg text-brand-text">
    <Navbar />

    <!-- HERO -->
    <section class="relative flex justify-center bg-brand-surface">
      <div class="mx-auto flex w-full max-w-6xl flex-col items-center px-4 pt-14 pb-10 text-center sm:px-6 md:pt-20 lg:pt-20">
        <img
          :src="heroLogo"
          alt="musicexams.help"
          class="h-auto w-80 object-contain sm:w-[26rem] md:w-[34rem] lg:w-[40rem] xl:w-[46rem]"
        />

        <MyTextConstructor
          variant="display"
          alignment="center"
          fontFamily="display"
          spacing="tight"
          class="mt-6 max-w-4xl md:!text-5xl lg:!text-6xl"
        >
          <template #myTitle>
            Your guide to Trinity music exams
          </template>
        </MyTextConstructor>

        <MyTextConstructor
          alignment="center"
          bodyVariant="body"
          spacing="none"
          class="mt-4 max-w-3xl text-brand-text-soft md:!text-xl lg:!text-2xl"
        >
          Free guidance, incentives and support for teachers, parents and students.
          Use centre code
          <span class="font-semibold text-brand-text">{{ referralCode }}</span>
          when booking to unlock it all.
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
    <div class="w-full border-y border-brand-border bg-brand-surface-soft py-6 md:py-12">
      <div class="mx-auto max-w-5xl px-6 py-5">
        <div class="flex flex-col items-center gap-6 sm:flex-row sm:justify-between">
          <div class="text-center sm:text-left">
            <MyTextConstructor
              alignment="left"
              bodyVariant="body"
              spacing="none"
              class="text-brand-text-soft md:!text-xl lg:!text-2xl"
            >
              Run by a local music teacher in partnership with
              <span class="font-semibold text-brand-text">
                Trinity Registered Exam Centre 120
              </span>
            </MyTextConstructor>
            <MyTextConstructor
              alignment="left"
              bodyVariant="muted"
              spacing="none"
              class="mt-1 text-brand-text-soft md:!text-lg lg:!text-xl"
            >
              Supporting teachers, parents and students across the UK
            </MyTextConstructor>
          </div>

          <img
            :src="trinityCentreLogo"
            alt="Trinity Registered Exam Centre 120"
            class="h-24 w-auto sm:h-28 md:h-32 lg:h-36 xl:h-40"
          />
        </div>
      </div>
    </div>

    <!-- WHY -->
    <section id="why" class="border-b border-brand-border bg-brand-surface">
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
            class="mt-3 md:!text-3xl lg:!text-4xl"
          >
            <template #myTitle>
              Something for everyone
            </template>
          </MyTextConstructor>

          <MyTextConstructor
            bodyVariant="body"
            spacing="none"
            class="mt-4 max-w-2xl text-brand-text-soft md:!text-xl lg:!text-2xl"
          >
            Whether you teach music, you are a parent navigating exams for the first time,
            or a student preparing for your next grade — centre code {{ referralCode }} connects
            you to guidance, recognition and real support.
          </MyTextConstructor>
        </div>

        <div class="mt-10 grid gap-5 md:grid-cols-3">
          <div
            v-for="card in whyCards"
            :key="card.id"
            class="rounded-2xl border-[6px] border-brand-primary bg-brand-surface-soft p-6 shadow-sm transition-all duration-200 hover:-translate-y-1 hover:shadow-md"
          >
            <MyTextConstructor
              variant="subheading"
              spacing="tight"
              textColor="text-brand-primary"
              class="md:!text-2xl lg:!text-3xl"
            >
              <template #myTitle>
                {{ card.title }}
              </template>
            </MyTextConstructor>

            <MyTextConstructor
              subTitleVariant="muted"
              spacing="none"
              textColor="text-brand-text-soft"
              class="mt-2 md:!text-lg lg:!text-xl"
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
    <section id="incentives" class="border-t border-brand-border bg-brand-bg">
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
              class="mt-3 max-w-2xl md:!text-3xl lg:!text-4xl"
            >
              <template #myTitle>
                Hard work deserves more than a certificate
              </template>
            </MyTextConstructor>

            <MyTextConstructor
              bodyVariant="body"
              spacing="none"
              class="mt-5 max-w-2xl text-brand-text-soft md:!text-xl lg:!text-2xl"
            >
              Students put in the practice. Teachers put in the hours. Centre code {{ referralCode }}
              connects you to real benefits — prize draws, recognition, funding support and
              discounts on exam materials.
            </MyTextConstructor>
          </div>

          <!-- RIGHT -->
          <div class="lg:pt-12">
            <div class="space-y-4">
              <div
                v-for="item in incentives"
                :key="item.title"
                class="rounded-2xl border border-brand-border bg-brand-surface p-5 shadow-sm"
              >
                <MyTextConstructor variant="button-lg" spacing="tight" textColor="text-brand-primary" class="md:!text-xl lg:!text-xl">
                  <template #myTitle>{{ item.title }}</template>
                </MyTextConstructor>
                <MyTextConstructor bodyVariant="muted" spacing="none" class="mt-2 text-brand-text-soft md:!text-base lg:!text-lg">
                  {{ item.detail }}
                </MyTextConstructor>
              </div>
            </div>
          </div>
        </div>

        <!-- BOTTOM ROW -->
        <div class="mt-10">
          <div class="mx-auto max-w-7xl">
            <div class="overflow-hidden rounded-[2rem] bg-brand-surface p-3 shadow-xl ring-1 ring-brand-border">
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
    <section class="border-t border-brand-border bg-brand-surface">
      <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8 lg:py-16">
        <MyTextConstructor
          variant="heading"
          fontFamily="display"
          alignment="center"
          spacing="tight"
          class="md:!text-3xl lg:!text-4xl"
        >
          <template #myTitle>
            Ready to get started?
          </template>
        </MyTextConstructor>

        <MyTextConstructor
          subTitleVariant="muted"
          alignment="center"
          spacing="none"
          class="mt-3 md:!text-lg lg:!text-xl"
        >
          <template #mySubTitle>
            Choose your next step — whether you are ready to book or want to learn more first.
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
    <section id="faq" class="border-t border-brand-border bg-brand-surface">
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
            class="mt-3 md:!text-3xl lg:!text-4xl"
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
            header-bg-color="bg-brand-surface"
            header-text-color="text-brand-primary"
            header-hover-bg-color="hover:bg-brand-bg"
            border-color="border-brand-border"
            content-bg-color="bg-brand-surface"
          />
        </div>
      </div>
    </section>

    <!-- FOOTER -->
    <MyFooter />
  </div>
</template>