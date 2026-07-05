<!-- resources/js/pages/Books.vue -->
<script setup lang="ts">
import { usePageAnimation } from '@/composables/usePageAnimation'
import Head from '@/components/layouts/Head.vue'
import Navbar from '@/components/layouts/Navbar.vue'
import Breadcrumbs from '@/components/layouts/Breadcrumbs.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MyFooter from '@/components/layouts/MyFooter.vue'
import { Search, Monitor, BookOpen } from 'lucide-vue-next'

const { animClass } = usePageAnimation()

const pageMeta = {
  title: 'Buy Your Trinity Exam Book',
  description:
    'Find the exact Trinity exam book for your grade and instrument in our Piece Finder. Buy it as a digital ebook at 10% off, or in print via Amazon.',
  canonicalUrl: 'https://musicexams.help/books',
}

const breadcrumbPages = [{ name: 'Books', href: '/books', current: true }]
</script>

<template>
  <Head :title="pageMeta.title" :description="pageMeta.description" :canonical-url="pageMeta.canonicalUrl" />

  <div class="min-h-screen bg-black text-brand-text">
    <Navbar />

    <!-- HEADER -->
    <section class="bg-brand-surface pt-36 pb-10 md:pt-40">
      <div class="mx-auto max-w-4xl px-4 sm:px-6">
        <div class="mb-6"><Breadcrumbs :pages="breadcrumbPages" home-href="/" /></div>
        <div class="text-center">
          <div :class="animClass('fade-up', 0)">
            <MyTextConstructor variant="eyebrow" alignment="center" spacing="tight">
              <template #myTitle>Exam Books</template>
            </MyTextConstructor>
          </div>
          <div :class="animClass('fade-up', 1)">
            <MyTextConstructor variant="heading" fontFamily="display" alignment="center" spacing="tight" titleTag="h1" class="mt-3 md:!text-3xl lg:!text-4xl">
              <template #myTitle>Buy your exam book</template>
            </MyTextConstructor>
          </div>
          <div :class="animClass('fade-up', 2)">
            <p class="mx-auto mt-4 max-w-2xl text-base text-brand-text-soft md:text-lg">
              Find the exact book for your grade and instrument in our Piece Finder — then buy it
              as a digital ebook (10% off) or in print.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- FINDER CTA -->
    <section
      class="relative bg-cover bg-center"
      style="background-image: url('https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/blue_BG_7.jpg')"
    >
      <div class="absolute inset-0 bg-brand-primary/55"></div>
      <div class="relative mx-auto max-w-3xl px-4 py-14 sm:px-6 lg:py-20">
        <div :class="animClass('fade-up', 1)" class="overflow-hidden rounded-2xl border-4 border-brand-accent bg-white/10 shadow-2xl backdrop-blur-sm">
          <div class="flex items-center justify-center gap-3 bg-black px-5 py-3 sm:px-6">
            <Search class="h-6 w-6 shrink-0 text-brand-accent" />
            <p class="text-base font-semibold text-white sm:text-lg">Find your exact book</p>
          </div>
          <div class="p-6 text-center text-white">
            <p class="text-base sm:text-lg">
              Search by instrument, grade and exam type to find the official book your piece is in —
              and listen to each piece before you choose.
            </p>
            <ul class="mx-auto mt-5 max-w-md space-y-2 text-left text-sm text-white/80 sm:text-base">
              <li class="flex items-start gap-2"><Monitor class="mt-0.5 h-4 w-4 shrink-0 text-brand-accent" /> <span><strong class="text-white">Digital ebook</strong> — 10% off from Trinity College London Press, instant download</span></li>
              <li class="flex items-start gap-2"><BookOpen class="mt-0.5 h-4 w-4 shrink-0 text-brand-accent" /> <span><strong class="text-white">Print</strong> — the correct edition on Amazon, no guesswork</span></li>
            </ul>
            <div class="mt-6">
              <a href="/syllabus">
                <MyButtonConstructor variant="light" size="large" :icon="Search" icon-position="left">
                  Open the Piece Finder
                </MyButtonConstructor>
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- TEACHER NUDGE — trade account (off-site, personal-network only) -->
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

    <MyFooter variant="gradient" />
  </div>
</template>
