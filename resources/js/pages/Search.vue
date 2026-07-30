<!-- resources/js/pages/Search.vue -->
<script setup lang="ts">
import { computed, ref } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline'
import { usePageAnimation } from '@/composables/usePageAnimation'
import Head from '@/components/layouts/Head.vue'
import Navbar from '@/components/layouts/Navbar.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyFooter from '@/components/layouts/MyFooter.vue'
import Breadcrumbs from '@/components/layouts/Breadcrumbs.vue'
import { runSearch } from '@/data/searchIndex'

const { animClass } = usePageAnimation()

const page = usePage()

const breadcrumbPages = [
  { name: 'Search', href: '/search', current: true },
]

const pageMeta = {
  title: 'Search',
  description: 'Search musicExams.help for answers about Trinity music exams, booking, fees, certificates and centre 120.',
  canonicalUrl: 'https://musicexams.help/search',
}

const initialQuery = (() => {
  const raw = page.url.split('?')[1] ?? ''
  return new URLSearchParams(raw).get('q') ?? ''
})()

const query = ref(initialQuery)

const results = computed(() => runSearch(query.value))
const hasQuery = computed(() => query.value.trim().length > 0)

const submit = () => {
  const q = query.value.trim()
  router.visit('/search' + (q ? '?q=' + encodeURIComponent(q) : ''), {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}
</script>

<template>
  <Head :title="pageMeta.title" :description="pageMeta.description" :canonical-url="pageMeta.canonicalUrl" />

  <div class="min-h-screen bg-black text-brand-text">
    <Navbar />

    <section class="bg-brand-surface pt-36 pb-10 md:pt-40 lg:pt-40">
      <div class="mx-auto max-w-4xl px-4 sm:px-6">
        <div class="mb-6">
          <Breadcrumbs :pages="breadcrumbPages" home-href="/" />
        </div>

        <div :class="animClass('fade-up', 0)">
          <MyTextConstructor variant="eyebrow" alignment="left" spacing="tight">
            <template #myTitle>Search</template>
          </MyTextConstructor>
        </div>

        <div :class="animClass('fade-up', 1)">
          <MyTextConstructor
            variant="heading"
            fontFamily="display"
            alignment="left"
            spacing="tight"
            titleTag="h1"
            class="mt-3 md:!text-3xl lg:!text-4xl"
          >
            <template #myTitle>Search musicExams.help</template>
          </MyTextConstructor>
        </div>

        <form class="relative mt-6" @submit.prevent="submit">
          <MagnifyingGlassIcon class="pointer-events-none absolute left-4 top-1/2 h-6 w-6 -translate-y-1/2 text-slate-400" />
          <input
            v-model="query"
            type="search"
            autofocus
            placeholder="Search"
            aria-label="Search the site"
            class="h-14 w-full rounded-2xl border border-brand-border bg-white pl-12 pr-4 text-lg text-slate-800 shadow-sm placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-accent"
          />
        </form>
      </div>
    </section>

    <section class="bg-black">
      <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:py-16">
        <div v-if="hasQuery" class="mb-6">
          <MyTextConstructor variant="muted" alignment="left" spacing="none" textColor="text-brand-text-soft">
            <template #myTitle>
              {{ results.length }}
              {{ results.length === 1 ? 'result' : 'results' }} for “{{ query.trim() }}”
            </template>
          </MyTextConstructor>
        </div>

        <div v-if="hasQuery && results.length" class="space-y-4">
          <a
            v-for="(result, index) in results"
            :key="result.id"
            :href="result.url"
            :class="animClass('fade-up', Math.min(index, 4))"
            class="block rounded-2xl border border-brand-primary bg-brand-surface p-5 shadow-sm transition hover:bg-brand-surface-soft sm:p-6"
          >
            <span class="text-xs font-semibold uppercase tracking-[0.08em] text-brand-accent">
              {{ result.section }}
            </span>
            <MyTextConstructor
              variant="button-lg"
              alignment="left"
              spacing="none"
              textColor="text-brand-primary"
              class="mt-1"
            >
              <template #myTitle>{{ result.title }}</template>
            </MyTextConstructor>
            <p class="mt-2 text-base leading-relaxed text-brand-text-soft md:text-lg">
              {{ result.snippet }}
            </p>
          </a>
        </div>

        <div
          v-else-if="hasQuery"
          class="rounded-2xl bg-brand-surface py-12 text-center shadow-sm"
        >
          <MyTextConstructor variant="subheading" alignment="center" spacing="tight">
            <template #myTitle>No matches found</template>
          </MyTextConstructor>
          <MyTextConstructor variant="muted" alignment="center" spacing="none" class="mt-2">
            <template #myTitle>
              Try a different word, or head to the FAQ — and if you still can’t find it, get in touch and we’ll help.
            </template>
          </MyTextConstructor>
          <div class="mt-6 flex flex-wrap justify-center gap-3">
            <a href="/faq" class="rounded-xl border border-brand-border bg-brand-surface-soft px-4 py-2 text-sm font-semibold text-brand-primary transition hover:bg-brand-bg">
              Browse the FAQ
            </a>
            <a href="/contact" class="rounded-xl border border-brand-border bg-brand-surface-soft px-4 py-2 text-sm font-semibold text-brand-primary transition hover:bg-brand-bg">
              Contact us
            </a>
          </div>
        </div>

        <div
          v-else
          class="rounded-2xl bg-brand-surface py-12 text-center shadow-sm"
        >
          <MyTextConstructor variant="subheading" alignment="center" spacing="tight">
            <template #myTitle>What are you looking for?</template>
          </MyTextConstructor>
          <MyTextConstructor variant="muted" alignment="center" spacing="none" class="mt-2">
            <template #myTitle>
              Start typing above to search the site.
            </template>
          </MyTextConstructor>
        </div>
      </div>
    </section>

    <MyFooter variant="gradient" />
  </div>
</template>
