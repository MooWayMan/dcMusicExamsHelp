<!-- resources/js/components/layouts/Breadcrumbs.vue -->
<script setup lang="ts">
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
</script>

<template>
  <div class="sticky top-20 z-40 -mx-4 -mt-6 mb-4 border-b border-brand-border/50 bg-brand-surface/95 px-4 py-3 backdrop-blur sm:-mx-6 sm:px-6">
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
          v-for="page in props.pages"
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
</template>