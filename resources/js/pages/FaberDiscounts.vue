<!-- resources/js/pages/FaberDiscounts.vue -->
<script setup lang="ts">
import { usePageAnimation } from '@/composables/usePageAnimation'
import Head from '@/components/layouts/Head.vue'
import Navbar from '@/components/layouts/Navbar.vue'
import Breadcrumbs from '@/components/layouts/Breadcrumbs.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MyAccordionConstructor from '@/components/reusables/MyAccordionConstructor.vue'
import MyGlassCardConstructor from '@/components/reusables/MyGlassCardConstructor.vue'
import MyFooter from '@/components/layouts/MyFooter.vue'
import {
  Users,
  Gift,
  ShoppingBag,
  ArrowRight,
  CheckCircle,
  Percent,
  Mail,
} from 'lucide-vue-next'

const { animClass } = usePageAnimation()

const pageMeta = {
  title: 'Exam Book Discounts for Teachers — musicExams.help',
  description:
    'Music teachers can save on Trinity exam books with trade discounts, ebook savings, free books and more through Faber Music and Trinity College London Press.',
}

const breadcrumbPages = [
  { name: 'For Teachers', href: '/for-teachers', current: false },
  { name: 'Exam Book Discounts', href: '/for-teachers/faber-discounts', current: true },
]

/* ───────────────────────────────────
   INFOGRAPHIC — visual pathway data
   ─────────────────────────────────── */
const pathwaySteps = [
  {
    id: 1,
    icon: Users,
    label: 'Join the Teachers\' Community',
    tagline: 'Free to join — 1,000+ UK music teachers',
    colour: 'brand-accent',
    bgRing: 'bg-brand-accent/10',
    iconColour: 'text-brand-accent',
    borderColour: 'border-brand-accent',
  },
  {
    id: 2,
    icon: Percent,
    label: '10% off ebooks for your students',
    tagline: 'Parents and students save 10% on digital exam books',
    colour: 'brand-accent',
    bgRing: 'bg-brand-accent/10',
    iconColour: 'text-brand-accent',
    borderColour: 'border-brand-accent',
  },
  {
    id: 3,
    icon: ShoppingBag,
    label: 'Get set up with a trade account',
    tagline: 'Exclusive discount on all physical Faber and Trinity Press books',
    colour: 'brand-accent',
    bgRing: 'bg-brand-accent/10',
    iconColour: 'text-brand-accent',
    borderColour: 'border-brand-accent',
  },
  {
    id: 4,
    icon: Gift,
    label: 'Claim your free book each year',
    tagline: 'Opens mid-June — first come, first served',
    colour: 'brand-accent',
    bgRing: 'bg-brand-accent/10',
    iconColour: 'text-brand-accent',
    borderColour: 'border-brand-accent',
  },
]

/* ───────────────────────────────────
   DETAIL CARDS
   ─────────────────────────────────── */
const discountCards = [
  {
    icon: Percent,
    title: '10% off ebooks for your students',
    detail:
      'Trinity College London Press sells digital editions of exam books through their online store. When students or parents buy an ebook through the link on our site, they get 10% off automatically. Just point your parents to musicExams.help and tell them to use our ebook link when ordering — no setup needed, the discount is built in.',
    link: 'https://store.trinitycollege.com/?aff=99',
    linkText: 'Browse the ebook store',
  },
  {
    icon: ShoppingBag,
    title: 'Exclusive trade discount on physical books',
    detail:
      'Registered music teachers can get an exclusive discount on all physical books published by Faber Music and Trinity College London Press — not just exam books, but everything in their catalogue. Get in touch and we\'ll walk you through the setup — it only takes a few minutes and we\'ll make sure you\'re on the right pricing tier from day one.',
    link: 'mailto:musicexams@musicexams.help?subject=Setting%20up%20a%20Faber%20trade%20account&body=Hi%2C%0A%0AI%27m%20interested%20in%20setting%20up%20a%20Faber%20trade%20account%20for%20the%20teacher%20discount%20on%20exam%20books.%20Could%20you%20let%20me%20know%20how%20to%20get%20started%3F%0A%0AThanks',
    linkText: 'Get in touch to find out more',
  },
  {
    icon: Users,
    title: 'Faber Teachers\' Community',
    detail:
      'A free community of over 1,000 UK music teachers. Members get access to CPD webinars, exclusive discounts, giveaways, first-look at new publications, and author interviews. Loyal Teachers Club members get 40% off Faber Piano Adventures. You also need to be a member to take part in the Free Books programme each year.',
    link: 'https://www.fabermusic.com/ed/teachers-community',
    linkText: 'Join the Teachers\' Community',
  },
  {
    icon: Gift,
    title: 'Free Books for Teachers',
    detail:
      'Every year from mid-June, Faber opens a programme where teachers can claim one free book, posted free of charge. It is first come, first served and goes quickly — <a href="https://calendar.google.com/calendar/u/0?cid=Y19lMjgzOGE4YmE2OTMyNWFhNWRlYzdhNGVlYzcwNTFmMjNiZjhmMmUzMDdhOWM3ZTQ4NDM1MzgyYTA1YzRhN2VlQGdyb3VwLmNhbGVuZGFyLmdvb2dsZS5jb20" target="_blank" rel="noopener noreferrer" class="font-semibold text-brand-accent underline hover:opacity-70">subscribe to our Google Calendar</a> and we\'ll remind you when it opens. You need to be a member of the Teachers\' Community to take part, so make sure you join before June.',
    link: 'https://www.fabermusic.com/freebooksforteachers',
    linkText: 'Visit the Free Books page (opens mid-June)',
  },
]

/* ───────────────────────────────────
   FAQ
   ─────────────────────────────────── */
const faqs = [
  {
    question: 'Does the trade discount only apply to exam books?',
    answer:
      'No. The trade discount covers everything published by Faber Music and Trinity College London Press — exam books, sight-reading, scales, repertoire collections, teaching resources, and more.',
  },
  {
    question: 'How do I set up a trade account?',
    answer:
      'Get in touch with us and we\'ll walk you through the setup — we\'ll make sure you\'re on the right pricing tier from day one. It\'s straightforward — you fill out a short form and the account is usually set up within a few days.',
  },
  {
    question: 'Is there a minimum order for the trade discount?',
    answer:
      'No. Once your trade account is active, the discount applies to every order regardless of size. You can order a single book or a whole class set.',
  },
  {
    question: 'Do I need to be a Trinity teacher to get these discounts?',
    answer:
      'The trade account is available to any registered music teacher — you don\'t need to teach Trinity syllabuses specifically. The 10% ebook discount is available to anyone who uses the link on our site.',
  },
  {
    question: 'Can parents order directly with the trade discount?',
    answer:
      'The trade account is only available to teachers, not parents. However, many teachers order books on behalf of their students and pass the saving on — or sell at retail price as a small extra income stream.',
  },
  {
    question: 'What is the Faber Teachers\' Community?',
    answer:
      'It\'s a free online community for UK music teachers run by Faber Music. Members get CPD webinars, early access to new publications, author interviews, giveaways, and eligibility for the Free Books for Teachers programme each year.',
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
              <template #myTitle>Exam Book Discounts</template>
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
              <template #myTitle>Save on the books your students need</template>
            </MyTextConstructor>
          </div>

          <div :class="animClass('fade-up', 2)">
            <p class="mx-auto mt-4 max-w-2xl text-base text-brand-text-soft sm:text-base md:text-lg lg:text-xl">
              Faber Music and Trinity College London Press publish the books behind Trinity exams.
              As a music teacher, you can access significant discounts on both physical and digital editions — here's how.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- INFOGRAPHIC PATHWAY -->
    <section
      class="relative bg-cover bg-center bg-no-repeat"
      style="background-image: url('https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/blue_BG_7.jpg')"
    >
      <div class="absolute inset-0 bg-black/50"></div>
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
            <template #myTitle>Your discount pathway</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-2xl text-center text-base text-white/80 sm:text-base md:text-lg lg:text-xl">
            Four programmes, all designed to help music teachers save money on resources.
          </p>
        </div>

        <!-- Pathway visual — vertical on mobile, horizontal on lg -->
        <div :class="animClass('fade-up', 2)" class="mt-10">
          <!-- Desktop: horizontal pathway -->
          <div class="hidden lg:block">
            <div class="flex items-start justify-between">
              <div
                v-for="(step, index) in pathwaySteps"
                :key="step.id"
                class="flex flex-1 items-start"
              >
                <!-- Step node -->
                <div class="flex w-full flex-col items-center text-center">
                  <div
                    :class="[step.bgRing, step.borderColour, 'flex h-16 w-16 items-center justify-center rounded-full border-2']"
                  >
                    <component :is="step.icon" :class="[step.iconColour, 'h-7 w-7']" />
                  </div>
                  <p class="mt-3 text-base font-semibold text-white sm:text-base">{{ step.label }}</p>
                  <p class="mt-1 max-w-[180px] text-xs text-white/60 sm:text-sm">{{ step.tagline }}</p>
                </div>
                <!-- Connector arrow (not after last) -->
                <div
                  v-if="index < pathwaySteps.length - 1"
                  class="mt-7 flex shrink-0 items-center px-2"
                >
                  <div class="h-0.5 w-6 bg-white/30" />
                  <ArrowRight class="h-4 w-4 text-white/60" />
                </div>
              </div>
            </div>
          </div>

          <!-- Mobile/tablet: vertical pathway -->
          <div class="lg:hidden">
            <div class="space-y-0">
              <div
                v-for="(step, index) in pathwaySteps"
                :key="step.id"
              >
                <div class="flex items-start gap-4">
                  <div class="flex flex-col items-center">
                    <div
                      :class="[step.bgRing, step.borderColour, 'flex h-14 w-14 shrink-0 items-center justify-center rounded-full border-2 sm:h-16 sm:w-16']"
                    >
                      <component :is="step.icon" :class="[step.iconColour, 'h-6 w-6 sm:h-7 sm:w-7']" />
                    </div>
                    <!-- Vertical connector -->
                    <div
                      v-if="index < pathwaySteps.length - 1"
                      class="mt-1 h-8 w-0.5 bg-white/30"
                    />
                  </div>
                  <div class="pt-2">
                    <p class="text-base font-semibold text-white sm:text-base">{{ step.label }}</p>
                    <p class="mt-0.5 text-xs text-white/60 sm:text-sm">{{ step.tagline }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- DETAIL CARDS -->
    <section
      class="relative"
      style="background-image: url('https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/blue_BG_8.jpg'); background-size: cover; background-position: center;"
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
            <template #myTitle>What's available</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-10">
          <MyGlassCardConstructor :cards="discountCards" :columns="2" />
        </div>
      </div>
    </section>

    <!-- HOW WE HELP — soft get-in-touch section -->
    <section
      id="get-in-touch"
      class="relative bg-cover bg-center bg-no-repeat"
      style="background-image: url('https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/blue_BG_9.jpg')"
    >
      <div class="absolute inset-0 bg-black/50"></div>
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
            <template #myTitle>How we help</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-2xl text-center text-base text-white/80 sm:text-base md:text-lg lg:text-xl">
            Setting up discount accounts can feel confusing if you haven't done it before.
            We've been through the process ourselves and are happy to walk you through it — we'll make sure you're on the right pricing tier from day one. Just get in touch.
          </p>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-10 overflow-hidden rounded-2xl border-4 border-brand-accent bg-white/10 shadow-2xl backdrop-blur-sm">
          <div class="flex items-center justify-center gap-3 bg-black px-5 py-3 sm:px-6">
            <CheckCircle class="h-5 w-5 shrink-0 text-brand-accent sm:h-6 sm:w-6" />
            <p class="text-base font-semibold text-white sm:text-lg">What we can do</p>
          </div>
          <div class="p-6">
            <ul class="space-y-4">
              <li class="flex items-start gap-3">
                <CheckCircle class="mt-0.5 h-5 w-5 shrink-0 text-brand-accent" />
                <span class="text-base text-white sm:text-base md:text-lg">
                  We'll explain which discounts apply to your situation
                </span>
              </li>
              <li class="flex items-start gap-3">
                <CheckCircle class="mt-0.5 h-5 w-5 shrink-0 text-brand-accent" />
                <span class="text-base text-white sm:text-base md:text-lg">
                  We'll guide you through the trade account setup and make sure you're on the right pricing tier from day one
                </span>
              </li>
              <li class="flex items-start gap-3">
                <CheckCircle class="mt-0.5 h-5 w-5 shrink-0 text-brand-accent" />
                <span class="text-base text-white sm:text-base md:text-lg">
                  We'll point you to the Teachers' Community and Free Books programme
                </span>
              </li>
              <li class="flex items-start gap-3">
                <CheckCircle class="mt-0.5 h-5 w-5 shrink-0 text-brand-accent" />
                <span class="text-base text-white sm:text-base md:text-lg">
                  No pressure, no cost — just practical help from someone who uses these programmes daily
                </span>
              </li>
            </ul>
          </div>
        </div>

        <div :class="animClass('fade-up', 3)" class="mt-8 text-center">
          <a href="mailto:musicexams@musicexams.help?subject=Exam%20book%20discounts%20%E2%80%93%20I%27d%20like%20to%20know%20more&body=Hi%2C%0A%0AI%27m%20a%20music%20teacher%20and%20I%27d%20like%20to%20know%20more%20about%20the%20exam%20book%20discounts%20available.%0A%0AThanks">
            <MyButtonConstructor variant="light" size="large" :icon="Mail" icon-position="left">
              Email Us
            </MyButtonConstructor>
          </a>
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
            <template #myTitle>Common questions about discounts</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-8">
          <MyAccordionConstructor
            :items="faqs.map((faq, index) => ({
              id: index + 1,
              question: faq.question,
              answer: faq.answer,
            }))"
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
            <template #myTitle>Questions? We're here to help.</template>
          </MyTextConstructor>
          <div class="mt-4">
            <a href="mailto:musicexams@musicexams.help?subject=Question%20about%20exam%20book%20discounts&body=Hi%2C%0A%0A">
              <MyButtonConstructor variant="light" size="large" :icon="Mail" icon-position="left">
                Email Us
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
