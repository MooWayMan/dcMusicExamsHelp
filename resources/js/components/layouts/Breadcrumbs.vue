<!-- resources/js/components/layouts/Breadcrumbs.vue -->
<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { ChevronRightIcon, HomeIcon } from '@heroicons/vue/20/solid'
import { Link } from '@inertiajs/vue3'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'

interface BreadcrumbItem {
  name: string
  href: string
  current: boolean
}

interface Props {
  pages: BreadcrumbItem[]
  homeHref?: string
}

const props = withDefaults(defineProps<Props>(), {
  homeHref: '/',
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
</script>

<template>
  <!-- Fixed bar sits directly below the h-20 navbar -->
  <div class="fixed left-0 right-0 top-20 z-40 border-b border-brand-border/50 bg-white">
    <div class="mx-auto max-w-7xl px-4 py-3 sm:px-6 lg:px-8">
      <nav class="flex items-center text-brand-text-soft" aria-label="Breadcrumb">
        <ol role="list" class="flex items-center space-x-2">

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
    </div>
  </div>
</template>
