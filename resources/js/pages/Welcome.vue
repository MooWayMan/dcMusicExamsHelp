<!-- resources/js/pages/Welcome.vue -->
<script setup lang="ts">
import Head from '@/components/layouts/Head.vue'
import PageHeader from '@/components/reusables/PageHeader.vue'
import AppLayout from '@/components/layouts/AppLayout.vue'
import BlueBackgroundLayout from '@/components/layouts/BlueBackgroundLayout.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'

const pageMeta = {
  title: 'Book Your Trinity Music Exam',
  description: 'A simple step-by-step guide to booking your Trinity music exam using referral code 120.',
}

const breadcrumbPages = [
  { name: 'Home', href: '/', current: true },
]

const referralCode = '120'

// Replace this later with your actual Trinity booking / referral / guide URL
const bookingUrl = 'https://www.trinitycollege.com/'

const steps = [
  {
    number: '01',
    title: 'Read the guide',
    text: 'Understand the Trinity booking process quickly and clearly.',
  },
  {
    number: '02',
    title: 'Use code 120',
    text: 'Keep referral code 120 visible before you start booking.',
  },
  {
    number: '03',
    title: 'Book your exam',
    text: 'Move through the official booking process with more confidence and less confusion.',
  },
]

const faqs = [
  {
    question: 'What is MusicExams.help?',
    answer: 'MusicExams.help is an independent guide that makes Trinity music exam booking easier to understand.',
  },
  {
    question: 'Do I book through this website?',
    answer: 'No. This site helps explain the process, then points you to the official booking route.',
  },
  {
    question: 'Who is this for?',
    answer: 'Teachers, parents, and students who want the simplest way to book a Trinity music exam.',
  },
]
</script>

<template>
  <Head :title="pageMeta.title" :description="pageMeta.description" />

  <AppLayout :breadcrumbs="breadcrumbPages">
    <template #header>
      <PageHeader
        :title="pageMeta.title"
        :subtitle="pageMeta.description"
        eyebrow="musicexams.help"
        size="hero"
      />
    </template>

    <BlueBackgroundLayout variant="subtle" contained>
      <div class="mx-auto max-w-7xl space-y-8 px-4 py-8 md:px-6 md:py-10">
        <!-- Hero -->
        <section class="grid gap-8 lg:grid-cols-2 lg:items-center">
          <div class="rounded-3xl border border-brand-border bg-brand-surface p-6 shadow-sm md:p-8">
            <MyTextConstructor variant="heading" fontFamily="display" spacing="tight">
              <template #myTitle>
                Book your Trinity music exam without the confusion
              </template>

              <template #mySubTitle>
                A simple guide for teachers, parents, and students.
                Use referral code
                <span class="font-semibold text-brand-primary">{{ referralCode }}</span>
                when you book.
              </template>
            </MyTextConstructor>

            <div class="mt-6 flex flex-wrap gap-3">
              <a :href="bookingUrl" target="_blank" rel="noopener noreferrer">
                <MyButtonConstructor variant="primary" size="large">
                  Book Your Exam
                </MyButtonConstructor>
              </a>

              <a href="#how-it-works">
                <MyButtonConstructor variant="outline" size="large">
                  See How It Works
                </MyButtonConstructor>
              </a>
            </div>

            <div class="mt-6 rounded-2xl border border-brand-border bg-brand-bg p-5">
              <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-accent">
                Referral Code
              </p>
              <p class="mt-2 font-display text-4xl font-bold tracking-tight text-brand-primary">
                {{ referralCode }}
              </p>
              <p class="mt-2 text-sm leading-6 text-brand-text-soft">
                Keep this code ready when booking your Trinity music exam.
              </p>
            </div>
          </div>

          <div class="rounded-3xl border border-brand-border bg-brand-surface p-6 shadow-sm md:p-8">
            <MyTextConstructor variant="subheading" fontFamily="display">
              <template #myTitle>
                Why use this page
              </template>
            </MyTextConstructor>

            <div class="mt-6 space-y-4">
              <div class="rounded-2xl bg-brand-bg p-4">
                <p class="font-semibold text-brand-primary">Clear booking path</p>
                <p class="mt-1 text-sm leading-6 text-brand-text-soft">
                  No unnecessary clutter, just the steps that matter.
                </p>
              </div>

              <div class="rounded-2xl bg-brand-bg p-4">
                <p class="font-semibold text-brand-primary">Code 120 kept visible</p>
                <p class="mt-1 text-sm leading-6 text-brand-text-soft">
                  The referral code is easy to spot and remember.
                </p>
              </div>

              <div class="rounded-2xl bg-brand-bg p-4">
                <p class="font-semibold text-brand-primary">Built for Trinity first</p>
                <p class="mt-1 text-sm leading-6 text-brand-text-soft">
                  Start with Trinity now, with room later to expand to other exam boards.
                </p>
              </div>
            </div>
          </div>
        </section>

        <!-- How it works -->
        <section
          id="how-it-works"
          class="rounded-3xl border border-brand-border bg-brand-surface p-6 shadow-sm md:p-8"
        >
          <MyTextConstructor variant="heading" fontFamily="display">
            <template #myTitle>
              How it works
            </template>

            <template #mySubTitle>
              The quickest way to understand the process and move to booking.
            </template>
          </MyTextConstructor>

          <div class="mt-8 grid gap-4 md:grid-cols-3">
            <div
              v-for="step in steps"
              :key="step.number"
              class="rounded-2xl border border-brand-border bg-brand-bg p-5"
            >
              <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-accent">
                {{ step.number }}
              </p>
              <h3 class="mt-3 text-xl font-semibold text-brand-primary">
                {{ step.title }}
              </h3>
              <p class="mt-2 text-sm leading-6 text-brand-text-soft">
                {{ step.text }}
              </p>
            </div>
          </div>
        </section>

        <!-- CTA band -->
        <section class="rounded-3xl bg-brand-primary px-6 py-8 text-center shadow-sm md:px-8">
          <p class="text-sm font-semibold uppercase tracking-[0.2em] text-white/80">
            Booking reminder
          </p>
          <p class="mt-3 font-display text-4xl font-bold tracking-tight text-white md:text-5xl">
            Use Code {{ referralCode }}
          </p>
          <p class="mt-3 text-base leading-7 text-white/80">
            MusicExams.help is an independent guide that helps make Trinity booking simpler.
          </p>
        </section>

        <!-- FAQ -->
        <section class="rounded-3xl border border-brand-border bg-brand-surface p-6 shadow-sm md:p-8">
          <MyTextConstructor variant="heading" fontFamily="display">
            <template #myTitle>
              Common questions
            </template>
          </MyTextConstructor>

          <div class="mt-8 space-y-4">
            <div
              v-for="faq in faqs"
              :key="faq.question"
              class="rounded-2xl border border-brand-border bg-brand-bg p-5"
            >
              <h3 class="text-lg font-semibold text-brand-primary">
                {{ faq.question }}
              </h3>
              <p class="mt-2 text-sm leading-6 text-brand-text-soft">
                {{ faq.answer }}
              </p>
            </div>
          </div>
        </section>

        <!-- Final CTA -->
        <section class="rounded-3xl border border-brand-border bg-brand-surface p-8 text-center shadow-sm">
          <MyTextConstructor
            variant="heading"
            fontFamily="display"
            alignment="center"
          >
            <template #myTitle>
              Ready to book your Trinity music exam?
            </template>

            <template #mySubTitle>
              Use referral code {{ referralCode }} and continue to the official booking process.
            </template>
          </MyTextConstructor>

          <div class="mt-6 flex justify-center">
            <a :href="bookingUrl" target="_blank" rel="noopener noreferrer">
              <MyButtonConstructor variant="primary" size="large">
                Book Your Exam
              </MyButtonConstructor>
            </a>
          </div>
        </section>
      </div>
    </BlueBackgroundLayout>
  </AppLayout>
</template>