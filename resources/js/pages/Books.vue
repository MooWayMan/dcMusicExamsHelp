<!-- resources/js/pages/Books.vue -->
<script setup lang="ts">
import { usePageAnimation } from '@/composables/usePageAnimation'
import Head from '@/components/layouts/Head.vue'
import Navbar from '@/components/layouts/Navbar.vue'
import Breadcrumbs from '@/components/layouts/Breadcrumbs.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MyFooter from '@/components/layouts/MyFooter.vue'
import { BookOpen, Monitor, Mail, ExternalLink } from 'lucide-vue-next'

const { animClass } = usePageAnimation()

const pageMeta = {
  title: 'Buy your Trinity exam books — musicExams.help',
  description:
    'Find the exam book you need for your Trinity music exam. Digital ebooks from Trinity College London Press, or physical paperbacks via Amazon.',
}

const breadcrumbPages = [
  { name: 'Books', href: '/books', current: true },
]

// Amazon Associates UK tag (musicexamshelp-21) — earns commission on physical book sales
const amazonTag = 'musicexamshelp-21'
const amazonSearchUrl = `https://www.amazon.co.uk/s?k=trinity+college+london+exam+book&tag=${amazonTag}`

// Trinity Press ebook store — 10% off + Paul's affiliate
const trinityEbookUrl = 'https://store.trinitycollege.com/?aff=99'
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
              <template #myTitle>Exam Books</template>
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
              <template #myTitle>Buy your exam book</template>
            </MyTextConstructor>
          </div>

          <div :class="animClass('fade-up', 2)">
            <p class="mx-auto mt-4 max-w-2xl text-base text-brand-text-soft sm:text-base md:text-lg lg:text-xl">
              Every Trinity music exam candidate needs the official exam book for their grade and subject.
              Here's where to get it — digitally or in print.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- TWO CTA CARDS -->
    <section
      class="relative bg-cover bg-center bg-no-repeat"
      style="background-image: url('https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/blue_BG_7.jpg')"
    >
      <div class="absolute inset-0 bg-black/50"></div>
      <div class="relative mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 1)" class="grid gap-6 md:grid-cols-2">

          <!-- Digital / ebook card -->
          <div class="overflow-hidden rounded-2xl border-4 border-brand-accent bg-white/10 shadow-2xl backdrop-blur-sm">
            <div class="flex items-center justify-center gap-3 bg-black px-5 py-3 sm:px-6">
              <Monitor class="h-6 w-6 shrink-0 text-brand-accent" />
              <p class="text-base font-semibold text-white sm:text-lg">Digital — ebook</p>
            </div>
            <div class="p-6 text-white">
              <p class="text-base sm:text-base md:text-lg">
                Trinity College London Press publishes every exam book as a digital edition.
                <span class="inline-block rounded-full bg-brand-success px-3 py-0.5 text-sm font-semibold text-white">Save 10%</span>
                automatically when you buy through our link — no code needed.
              </p>
              <ul class="mt-4 space-y-2 text-sm text-white/80 sm:text-base">
                <li class="flex items-start gap-2"><span class="text-brand-accent">✓</span> Instant download — available on any device</li>
                <li class="flex items-start gap-2"><span class="text-brand-accent">✓</span> 10% off the list price</li>
                <li class="flex items-start gap-2"><span class="text-brand-accent">✓</span> No postage, no waiting</li>
              </ul>
              <div class="mt-6 text-center">
                <a :href="trinityEbookUrl" target="_blank" rel="noopener noreferrer">
                  <MyButtonConstructor variant="light" size="large" :icon="ExternalLink" icon-position="right">
                    Browse ebooks
                  </MyButtonConstructor>
                </a>
              </div>
            </div>
          </div>

          <!-- Paperback / Amazon card -->
          <div class="overflow-hidden rounded-2xl border-4 border-brand-accent bg-white/10 shadow-2xl backdrop-blur-sm">
            <div class="flex items-center justify-center gap-3 bg-black px-5 py-3 sm:px-6">
              <BookOpen class="h-6 w-6 shrink-0 text-brand-accent" />
              <p class="text-base font-semibold text-white sm:text-lg">Paperback — Amazon</p>
            </div>
            <div class="p-6 text-white">
              <p class="text-base sm:text-base md:text-lg">
                Prefer a physical book to write on? Search Amazon for your specific grade and instrument — the link below pre-fills a Trinity exam book search.
              </p>
              <ul class="mt-4 space-y-2 text-sm text-white/80 sm:text-base">
                <li class="flex items-start gap-2"><span class="text-brand-accent">✓</span> All grades and instruments stocked</li>
                <li class="flex items-start gap-2"><span class="text-brand-accent">✓</span> Prime delivery available</li>
                <li class="flex items-start gap-2"><span class="text-brand-accent">✓</span> Supports musicExams.help at no extra cost to you</li>
              </ul>
              <div class="mt-6 text-center">
                <a :href="amazonSearchUrl" target="_blank" rel="noopener noreferrer">
                  <MyButtonConstructor variant="light" size="large" :icon="ExternalLink" icon-position="right">
                    Search on Amazon
                  </MyButtonConstructor>
                </a>
              </div>
            </div>
          </div>

        </div>
      </div>
    </section>

    <!-- TEACHER NUDGE — low-key, personal-network-only -->
    <section class="bg-black">
      <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:py-12">
        <div :class="animClass('fade-up', 1)" class="rounded-2xl border border-brand-border bg-brand-surface-soft p-6 text-center">
          <p class="text-sm text-brand-text-soft sm:text-base">
            <span class="font-semibold text-brand-text">Music teacher?</span>
            If you'd like to discuss a trade account for discounted physical books,
            <a
              href="mailto:musicexams@musicexams.help?subject=Trade%20account%20for%20exam%20books&body=Hi%20Paul%2C%0A%0AI%27m%20a%20music%20teacher%20and%20I%27d%20like%20to%20know%20more%20about%20a%20trade%20account%20for%20exam%20books.%0A%0AThanks"
              class="font-semibold text-brand-accent underline hover:opacity-70"
            >
              get in touch
            </a>
            and I'll walk you through the options.
          </p>
        </div>
      </div>
    </section>

    <!-- FOOTER -->
    <MyFooter variant="gradient" />
  </div>
</template>
