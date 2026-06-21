<!-- resources/js/pages/Sitemap.vue -->
<!--
  Human-readable sitemap. Two reasons it exists:
  1. Internal-link discovery backstop. Plain HTML with <a href> links to
     every public page, linked from the footer, so any crawler that hits
     the homepage can follow links here and discover the whole site.
     (Originally added when /sitemap.xml was suspected to be blocked for
     Googlebot by Cloudflare — that was later DISPROVEN, see the route
     comment in routes/web.php — but this page remains useful for discovery.)
  2. Users occasionally want a one-page overview of what's here.
  Keep links plain — semantic <a> tags, no fancy hover JS, no client-
  rendering tricks. The whole point is that the markup is crawlable.
-->
<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { usePageAnimation } from '@/composables/usePageAnimation'
import Head from '@/components/layouts/Head.vue'
import Navbar from '@/components/layouts/Navbar.vue'
import Breadcrumbs from '@/components/layouts/Breadcrumbs.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyFooter from '@/components/layouts/MyFooter.vue'

const { animClass } = usePageAnimation()

const pageMeta = {
  title: 'Sitemap — musicExams.help',
  description:
    'Every public page on musicExams.help — Trinity exam guidance, recognition, prize draws and more — grouped by topic.',
}

const breadcrumbPages = [
  { name: 'Sitemap', href: '/sitemap', current: true },
]

type SitemapLink = { name: string; href: string }
type SitemapSection = { title: string; links: SitemapLink[] }

const sections: SitemapSection[] = [
  {
    title: 'Get started',
    links: [
      { name: 'Home', href: '/' },
      { name: 'Trinity Exam Information', href: '/trinity-exam-information' },
    ],
  },
  {
    title: 'For teachers',
    links: [
      { name: 'For Teachers', href: '/for-teachers' },
      { name: 'Switch to Centre 120', href: '/switch-to-centre-120' },
      { name: 'Teacher Awards', href: '/for-teachers/awards' },
    ],
  },
  {
    title: 'For parents & students',
    links: [
      { name: 'For Parents', href: '/for-parents' },
      { name: 'For Students', href: '/for-students' },
    ],
  },
  {
    title: 'Exam guide',
    links: [
      { name: 'Exam Guide overview', href: '/exam-guide' },
      { name: 'Grades Explained', href: '/exam-guide/grades-explained' },
      { name: 'Syllabuses', href: '/exam-guide/syllabuses' },
      { name: 'Digital Exams', href: '/exam-guide/digital-exams' },
      { name: 'What to Expect', href: '/exam-guide/what-to-expect' },
      { name: 'UCAS Points', href: '/exam-guide/ucas-points' },
      { name: 'Exam Fees', href: '/exam-fees' },
    ],
  },
  {
    title: 'Recognition & incentives',
    links: [
      { name: 'Hall of Fame (Recognition)', href: '/recognition' },
      { name: 'Incentives', href: '/incentives' },
    ],
  },
  {
    title: 'Resources',
    links: [
      { name: 'Piece Finder', href: '/syllabus' },
      { name: 'Books', href: '/books' },
      { name: 'FAQ', href: '/faq' },
    ],
  },
  {
    title: 'About',
    links: [
      { name: 'About musicExams.help', href: '/about' },
      { name: 'Contact', href: '/contact' },
    ],
  },
  {
    title: 'Legal',
    links: [
      { name: 'Privacy Policy', href: '/privacy' },
      { name: 'Terms of Use', href: '/terms' },
      { name: 'Cookie Policy', href: '/cookies' },
    ],
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
              <template #myTitle>Browse all pages</template>
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
              <template #myTitle>Sitemap</template>
            </MyTextConstructor>
          </div>

          <div :class="animClass('fade-up', 2)">
            <p class="mx-auto mt-4 max-w-2xl text-base text-brand-text-soft sm:text-base md:text-lg">
              Every public page on musicExams.help, grouped by topic. Use this if you're looking for something specific or just want an overview of what's here.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- LINK GRID -->
    <section class="bg-brand-surface pb-20">
      <div class="mx-auto max-w-4xl px-4 sm:px-6">
        <div class="grid gap-10 md:grid-cols-2">
          <div
            v-for="(section, sectionIndex) in sections"
            :key="section.title"
            :class="animClass('fade-up', sectionIndex)"
          >
            <h2 class="text-lg font-semibold text-brand-primary">
              {{ section.title }}
            </h2>
            <ul class="mt-3 space-y-2">
              <li v-for="link in section.links" :key="link.href">
                <Link
                  :href="link.href"
                  class="text-brand-accent hover:underline"
                >
                  {{ link.name }}
                </Link>
              </li>
            </ul>
          </div>
        </div>

        <p class="mt-12 text-xs text-brand-text-soft">
          An XML sitemap is also available at
          <a
            href="/sitemap.xml"
            class="text-brand-accent hover:underline"
          >/sitemap.xml</a>
          for crawlers that prefer it.
        </p>
      </div>
    </section>

    <MyFooter />
  </div>
</template>
