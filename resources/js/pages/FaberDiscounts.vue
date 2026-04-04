<!-- resources/js/pages/FaberDiscounts.vue -->
<script setup lang="ts">
import { usePageAnimation } from '@/composables/usePageAnimation'
import Head from '@/components/layouts/Head.vue'
import Navbar from '@/components/layouts/Navbar.vue'
import Breadcrumbs from '@/components/layouts/Breadcrumbs.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MyAccordionConstructor from '@/components/reusables/MyAccordionConstructor.vue'
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
    label: 'Earn commission on ebook sales',
    tagline: 'Parents save 10% — and you earn on every purchase',
    colour: 'brand-teal',
    bgRing: 'bg-brand-teal/10',
    iconColour: 'text-brand-teal',
    borderColour: 'border-brand-teal',
  },
  {
    id: 3,
    icon: ShoppingBag,
    label: 'Set up your trade account',
    tagline: '33% off all physical Faber and Trinity Press books',
    colour: 'brand-success',
    bgRing: 'bg-brand-success-soft',
    iconColour: 'text-brand-success',
    borderColour: 'border-brand-success',
  },
  {
    id: 4,
    icon: Gift,
    label: 'Claim your free book each year',
    tagline: 'Opens mid-June — first come, first served',
    colour: 'brand-primary',
    bgRing: 'bg-brand-primary/10',
    iconColour: 'text-brand-primary',
    borderColour: 'border-brand-primary',
  },
]

/* ───────────────────────────────────
   DETAIL CARDS
   ─────────────────────────────────── */
const discountCards = [
  {
    icon: Percent,
    title: 'Earn commission on ebook sales',
    detail:
      'Trinity College London Press sells digital editions of exam books through their online store. As a teacher, you can sign up for a free affiliate link. When students or parents buy an ebook through your link, they get 10% off — and you earn commission on every sale. It costs nothing to set up and takes a few minutes.',
    cta: 'Get in touch to set up your link',
    ctaUrl: 'mailto:musicexams@musicexams.help?subject=Ebook%20affiliate%20link%20%E2%80%93%20earning%20commission%20on%20ebook%20sales&body=Hi%2C%0A%0AI%27d%20like%20to%20set%20up%20my%20ebook%20affiliate%20link%20so%20my%20students%20and%20parents%20can%20get%2010%25%20off.%20Could%20you%20walk%20me%20through%20how%20to%20get%20started%3F%0A%0AThanks',
    ctaExternal: false,
    cta2: 'Browse the ebook store now',
    cta2Url: 'https://store.trinitycollege.com/?aff=99',
    iconBg: 'bg-brand-teal/10',
    iconColor: 'text-brand-teal',
  },
  {
    icon: ShoppingBag,
    title: '33% trade discount on physical books',
    detail:
      'Registered music teachers can set up a Faber Education Trade Account and get 33% off all physical books published by Faber Music and Trinity College London Press — not just exam books, but everything in their catalogue. The ordering process is done by spreadsheet at the moment, but the saving is well worth it.',
    cta: 'Get in touch to find out more',
    ctaUrl: 'mailto:musicexams@musicexams.help?subject=Setting%20up%20a%20Faber%20trade%20account&body=Hi%2C%0A%0AI%27m%20interested%20in%20setting%20up%20a%20Faber%20trade%20account%20to%20get%2033%25%20off%20exam%20books.%20Could%20you%20let%20me%20know%20how%20to%20get%20started%3F%0A%0AThanks',
    ctaExternal: false,
    iconBg: 'bg-brand-success-soft',
    iconColor: 'text-brand-success',
  },
  {
    icon: Users,
    title: 'Faber Teachers\' Community',
    detail:
      'A free community of over 1,000 UK music teachers. Members get access to CPD webinars, exclusive discounts, giveaways, first-look at new publications, and author interviews. Loyal Teachers Club members get 40% off Faber Piano Adventures. You also need to be a member to take part in the Free Books programme each year.',
    cta: 'Join the Teachers\' Community',
    ctaUrl: 'https://www.fabermusic.com/ed/teachers-community',
    ctaExternal: false,
    iconBg: 'bg-brand-accent/10',
    iconColor: 'text-brand-accent',
  },
  {
    icon: Gift,
    title: 'Free Books for Teachers',
    detail:
      'Every year from mid-June, Faber opens a programme where teachers can claim one free book, posted free of charge. It is first come, first served and goes quickly — so mark mid-June in your diary and check the link as soon as it opens. You need to be a member of the Teachers\' Community to take part, so make sure you join before June.',
    cta: 'Visit the Free Books page (opens mid-June)',
    ctaUrl: 'https://www.fabermusic.com/freebooksforteachers',
    ctaExternal: false,
    iconBg: 'bg-brand-primary/10',
    iconColor: 'text-brand-primary',
  },
]

/* ───────────────────────────────────
   FAQ
   ─────────────────────────────────── */
const faqs = [
  {
    question: 'Does the 33% discount only apply to exam books?',
    answer:
      'No. The trade discount covers everything published by Faber Music and Trinity College London Press — exam books, sight-reading, scales, repertoire collections, teaching resources, and more.',
  },
  {
    question: 'How do I set up a trade account?',
    answer:
      'Get in touch with us and we\'ll guide you through the process. It\'s straightforward — you fill out a short form and the account is usually set up within a few days.',
  },
  {
    question: 'Is there a minimum order for the trade discount?',
    answer:
      'No. Once your trade account is active, the 33% discount applies to every order regardless of size. You can order a single book or a whole class set.',
  },
  {
    question: 'Do I need to be a Trinity teacher to get these discounts?',
    answer:
      'The trade account is available to any registered music teacher — you don\'t need to teach Trinity syllabuses specifically. The ebook discount link works for anyone.',
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
    <section class="bg-brand-surface pt-24 pb-10 md:pt-28 lg:pt-28">
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
            <p class="mx-auto mt-4 max-w-2xl text-sm text-brand-text-soft sm:text-sm md:text-base lg:text-lg">
              Faber Music and Trinity College London Press publish the books behind Trinity exams.
              As a music teacher, you can access significant discounts on both physical and digital editions — here's how.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- INFOGRAPHIC PATHWAY -->
    <section class="border-t border-brand-border bg-brand-bg">
      <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            class="md:!text-2xl lg:!text-3xl"
          >
            <template #myTitle>Your discount pathway</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-2xl text-center text-sm text-brand-text-soft sm:text-sm md:text-base lg:text-lg">
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
                  <p class="mt-3 text-sm font-semibold text-brand-text sm:text-base">{{ step.label }}</p>
                  <p class="mt-1 max-w-[180px] text-xs text-brand-text-soft sm:text-sm">{{ step.tagline }}</p>
                </div>
                <!-- Connector arrow (not after last) -->
                <div
                  v-if="index < pathwaySteps.length - 1"
                  class="mt-7 flex shrink-0 items-center px-2"
                >
                  <div class="h-0.5 w-6 bg-brand-border" />
                  <ArrowRight class="h-4 w-4 text-brand-text-soft" />
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
                      class="mt-1 h-8 w-0.5 bg-brand-border"
                    />
                  </div>
                  <div class="pt-2">
                    <p class="text-sm font-semibold text-brand-text sm:text-base">{{ step.label }}</p>
                    <p class="mt-0.5 text-xs text-brand-text-soft sm:text-sm">{{ step.tagline }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- DETAIL CARDS -->
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
            <template #myTitle>What's available</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2">
          <div
            v-for="card in discountCards"
            :key="card.title"
            class="flex flex-col rounded-2xl border border-brand-border bg-brand-bg p-5 shadow-sm sm:p-6"
          >
            <div class="flex items-start gap-4">
              <div :class="[card.iconBg, 'flex h-10 w-10 shrink-0 items-center justify-center rounded-xl sm:h-12 sm:w-12']">
                <component :is="card.icon" :class="[card.iconColor, 'h-5 w-5 sm:h-6 sm:w-6']" />
              </div>
              <div class="flex-1">
                <p class="text-base font-semibold text-brand-text sm:text-lg">{{ card.title }}</p>
                <p class="mt-1 text-sm leading-snug text-brand-text-soft sm:text-sm md:text-base">{{ card.detail }}</p>
              </div>
            </div>
            <!-- CTA links if present -->
            <div v-if="card.cta" class="mt-4 flex flex-col gap-2 pl-14">
              <a
                v-if="card.ctaUrl"
                :href="card.ctaUrl"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center gap-1 text-sm font-semibold text-brand-accent transition hover:opacity-70"
              >
                {{ card.cta }}
                <ArrowRight class="h-4 w-4" />
              </a>
              <a
                v-else
                href="#get-in-touch"
                class="inline-flex items-center gap-1 text-sm font-semibold text-brand-accent transition hover:opacity-70"
              >
                {{ card.cta }}
                <ArrowRight class="h-4 w-4" />
              </a>
              <a
                v-if="card.cta2"
                :href="card.cta2Url"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center gap-1 text-sm font-semibold text-brand-accent transition hover:opacity-70"
              >
                {{ card.cta2 }}
                <ArrowRight class="h-4 w-4" />
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- HOW WE HELP — soft get-in-touch section -->
    <section id="get-in-touch" class="border-t border-brand-border bg-brand-bg">
      <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor
            variant="subheading"
            fontFamily="display"
            alignment="center"
            spacing="tight"
            class="md:!text-2xl lg:!text-3xl"
          >
            <template #myTitle>How we help</template>
          </MyTextConstructor>
          <p class="mx-auto mt-3 max-w-2xl text-center text-sm text-brand-text-soft sm:text-sm md:text-base lg:text-lg">
            Setting up discount accounts can feel confusing if you haven't done it before.
            We've been through the process ourselves and are happy to walk you through it — just get in touch.
          </p>
        </div>

        <div :class="animClass('fade-up', 2)" class="mt-10">
          <div class="mx-auto max-w-lg space-y-4">
            <div class="flex items-start gap-4 rounded-2xl border border-brand-border bg-brand-surface p-5 shadow-sm">
              <CheckCircle class="mt-0.5 h-5 w-5 shrink-0 text-brand-accent" />
              <p class="text-sm text-brand-text sm:text-sm md:text-base">
                We'll explain which discounts apply to your situation
              </p>
            </div>
            <div class="flex items-start gap-4 rounded-2xl border border-brand-border bg-brand-surface p-5 shadow-sm">
              <CheckCircle class="mt-0.5 h-5 w-5 shrink-0 text-brand-accent" />
              <p class="text-sm text-brand-text sm:text-sm md:text-base">
                We'll guide you through the trade account setup if you want one
              </p>
            </div>
            <div class="flex items-start gap-4 rounded-2xl border border-brand-border bg-brand-surface p-5 shadow-sm">
              <CheckCircle class="mt-0.5 h-5 w-5 shrink-0 text-brand-accent" />
              <p class="text-sm text-brand-text sm:text-sm md:text-base">
                We'll point you to the Teachers' Community and Free Books programme
              </p>
            </div>
            <div class="flex items-start gap-4 rounded-2xl border border-brand-border bg-brand-surface p-5 shadow-sm">
              <CheckCircle class="mt-0.5 h-5 w-5 shrink-0 text-brand-accent" />
              <p class="text-sm text-brand-text sm:text-sm md:text-base">
                No pressure, no cost — just practical help from someone who uses these programmes daily
              </p>
            </div>
          </div>
        </div>

        <div :class="animClass('fade-up', 3)" class="mt-8 text-center">
          <a href="mailto:musicexams@musicexams.help?subject=Exam%20book%20discounts%20%E2%80%93%20I%27d%20like%20to%20know%20more&body=Hi%2C%0A%0AI%27m%20a%20music%20teacher%20and%20I%27d%20like%20to%20know%20more%20about%20the%20exam%20book%20discounts%20available.%0A%0AThanks">
            <MyButtonConstructor variant="primary" size="large" :icon="Mail" icon-position="left">
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
              <MyButtonConstructor variant="primary" size="large" :icon="Mail" icon-position="left">
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
