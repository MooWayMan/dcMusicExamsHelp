<!-- resources/js/components/layouts/AppLayout.vue -->
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import MyFooter from './MyFooter.vue'
import Breadcrumbs from '@/components/layouts/Breadcrumbs.vue'
import Navbar from '@/components/layouts/Navbar.vue'

interface BreadcrumbItem {
  name: string
  href: string
  current: boolean
}

interface Props {
  fadeDelay?: number
  fadeDuration?: number
  protectionDuration?: number
  breadcrumbs?: BreadcrumbItem[]
  breadcrumbHomeHref?: string
  navbarFixed?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  fadeDelay: 200,
  fadeDuration: 600,
  protectionDuration: 800,
  breadcrumbs: () => [],
  breadcrumbHomeHref: '/',
  navbarFixed: true,
})

const isContentVisible = ref(false)
const showProtection = ref(true)

onMounted(() => {
  setTimeout(() => (isContentVisible.value = true), props.fadeDelay)
  setTimeout(() => (showProtection.value = false), props.protectionDuration)
})
</script>

<template>
  <div class="min-h-screen w-full overflow-x-hidden bg-brand-bg text-brand-text relative">
    <!-- NAVBAR -->
    <Navbar :fixed="props.navbarFixed" />

    <!-- MAIN -->
    <main :class="props.navbarFixed ? 'pt-20' : 'pt-0'">
      <div
        :class="[
          'transition-all ease-out',
          isContentVisible
            ? 'opacity-100 translate-y-0'
            : 'opacity-0 translate-y-6'
        ]"
        :style="`transition-duration: ${props.fadeDuration}ms;`"
      >
        <!-- HEADER SLOT -->
        <slot name="header" />

        <!-- BREADCRUMBS -->
        <div v-if="props.breadcrumbs && props.breadcrumbs.length > 0" class="px-4 sm:px-6 lg:px-8 mt-4">
          <div
            class="mx-auto max-w-7xl rounded-xl border border-brand-border bg-brand-surface px-4 py-3 shadow-sm overflow-x-auto"
          >
            <Breadcrumbs
              :pages="props.breadcrumbs"
              :homeHref="props.breadcrumbHomeHref"
            />
          </div>
        </div>

        <!-- PAGE CONTENT -->
        <div class="mt-6">
          <slot />
        </div>
      </div>
    </main>

    <!-- FOOTER -->
    <MyFooter />

    <!-- PROTECTION OVERLAY -->
    <div
      v-if="showProtection"
      class="fixed inset-0 z-50 flex items-center justify-center bg-brand-bg/40 backdrop-blur-sm"
      @click.prevent.stop
      @touchstart.prevent.stop
      @contextmenu.prevent.stop
      aria-hidden="true"
    >
      <div class="h-10 w-10 animate-pulse rounded-full bg-brand-accent/30"></div>
    </div>
  </div>
</template>