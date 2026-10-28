<!-- resources/js/components/layouts/Breadcrumbs.vue -->
<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { ChevronRightIcon, HomeIcon } from '@heroicons/vue/20/solid'
import { Link, router } from '@inertiajs/vue3'
import { Search } from 'lucide-vue-next'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import SiteSearchBox from '@/components/SiteSearchBox.vue'

interface BreadcrumbItem {
  name: string
  href: string
  current?: boolean
}

interface Props {
  pages: BreadcrumbItem[]
  homeHref?: string
  // Site search sits on the right of this bar on every page, so it is always
  // in view (Paul, 28 Oct 2026: "not everyone knows" the menu magnifier).
  // Off on the search page, which has its own box.
  showSearch?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  homeHref: '/',
  showSearch: true,
})

/* ── Dynamic parent page from ?from= query parameter ── */
const fromParam = ref('')
onMounted(() => {
  const params = new URLSearchParams(window.location.search)
  fromParam.value = params.get('from') || ''
})

const parentPages: Record<string, { name: string; href: string }> = {
  'for-teachers': { name: 'For Teachers', href: '/for-teachers' },
  'for-students': { name: 'For Students', href: '/for-students' },
  'for-parents': { name: 'For Parents', href: '/for-parents' },
  'incentives': { name: 'Incentives', href: '/incentives' },
  'exam-guide': { name: 'Exam Guide', href: '/exam-guide' },
  'exam-fees': { name: 'Exam Fees', href: '/exam-fees' },
  'faq': { name: 'FAQ', href: '/faq' },
  'trinity-exam-information': { name: 'Trinity Exam Information', href: '/trinity-exam-information' },
  'about': { name: 'About', href: '/about' },
  'what-to-expect': { name: 'What to Expect', href: '/exam-guide/what-to-expect' },
  'digital-exams': { name: 'Digital Exams', href: '/exam-guide/digital-exams' },
  'grades-explained': { name: 'Grades Explained', href: '/exam-guide/grades-explained' },
  'ucas-points': { name: 'UCAS Points', href: '/exam-guide/ucas-points' },
  'syllabuses': { name: 'Syllabuses', href: '/exam-guide/syllabuses' },
}

const resolvedPages = computed(() => {
  const parent = parentPages[fromParam.value]
  if (!parent) return props.pages

  // Don't add parent if it's already in the breadcrumb trail
  const alreadyIncluded = props.pages.some(p => p.href === parent.href)
  if (alreadyIncluded) return props.pages

  return [
    { name: parent.name, href: parent.href, current: false },
    ...props.pages,
  ]
})

/**
 * For non-current breadcrumb links, use history.back() to preserve
 * scroll position instead of a fresh Inertia visit.
 * This works because breadcrumbs represent the path you came from.
 */
function handleBreadcrumbClick(e: MouseEvent, page: BreadcrumbItem) {
  // Don't do anything for the current page breadcrumb
  if (page.current) return

  // Use browser back — preserves scroll position
  e.preventDefault()
  window.history.back()
}
</script>

<template>
  <!-- Fixed bar sits directly below the h-20 navbar -->
  <div class="fixed left-0 right-0 top-20 z-40 border-b border-brand-border/50 bg-white">
    <div class="mx-auto max-w-7xl px-4 py-3 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between gap-4">
      <nav class="min-w-0 flex items-center text-brand-text-soft" aria-label="Breadcrumb">
        <ol role="list" class="flex min-w-0 flex-wrap items-center space-x-2">

          <!-- Home -->
          <li>
            <Link
              :href="props.homeHref"
              class="flex items-center transition-colors hover:text-brand-primary"
            >
              <HomeIcon class="h-5 w-5 shrink-0" />
              <span class="sr-only">Home</span>
            </Link>
          </li>

          <!-- Items -->
          <li
            v-for="page in resolvedPages"
            :key="page.name"
            class="flex items-center gap-2"
          >
            <ChevronRightIcon class="h-4 w-4 shrink-0 opacity-60" />

            <Link
              :href="page.href"
              class="transition-colors"
              :class="page.current
                ? 'text-brand-primary font-semibold'
                : 'hover:text-brand-primary'"
              :aria-current="page.current ? 'page' : undefined"
              @click="(e: MouseEvent) => handleBreadcrumbClick(e, page)"
            >
              <MyTextConstructor
                variant="muted"
                alignment="left"
                :textColor="page.current ? 'text-brand-primary' : 'text-brand-text-soft'"
              >
                <template #myTitle>
                  {{ page.name }}
                </template>
              </MyTextConstructor>
            </Link>
          </li>

        </ol>
      </nav>

      <!-- Site search. The negative margin keeps the bar its usual height, so
           nothing below it moves. Phones and tablets get a labelled link to
           the search page instead, as a box does not fit beside the trail. -->
      <template v-if="props.showSearch">
        <div class="-my-1.5 hidden w-72 shrink-0 lg:block">
          <SiteSearchBox placeholder="Search the site" />
        </div>
        <Link href="/search" class="flex shrink-0 items-center gap-1.5 text-brand-accent lg:hidden">
          <Search class="h-5 w-5" aria-hidden="true" />
          <MyTextConstructor variant="button-sm" spacing="none" textColor="text-brand-accent">
            <template #myTitle>Search</template>
          </MyTextConstructor>
        </Link>
      </template>
      </div>
    </div>
  </div>
</template>
