<!-- resources/js/components/reusables/PageMaintenance.vue -->
<!--
  Per-page maintenance wrapper. Wraps page content and shows a
  friendly "back later" message when that page is toggled into
  maintenance mode from the admin panel.

  Usage:
  <PageMaintenance pageSlug="recognition">
    <template #default>
      ... normal page content ...
    </template>
  </PageMaintenance>

  Admin users always see the real page content (with a warning banner).
-->
<script setup lang="ts">
import { computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import { Construction, ArrowLeft, Home } from 'lucide-vue-next'
import Navbar from '@/components/Navbar.vue'
import MyFooter from '@/components/MyFooter.vue'
import Head from '@/components/layouts/Head.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'

interface Props {
  pageSlug: string
  pageTitle?: string
}

const props = withDefaults(defineProps<Props>(), {
  pageTitle: 'Page Maintenance',
})

const page = usePage()

const maintenancePages = computed(() => {
  return (page.props.maintenancePages as Record<string, string>) || {}
})

const isInMaintenance = computed(() => {
  return props.pageSlug in maintenancePages.value
})

const maintenanceMessage = computed(() => {
  return maintenancePages.value[props.pageSlug] || "We're updating this page with the latest data. Please check back shortly."
})

const isAdmin = computed(() => {
  const user = (page.props.auth as any)?.user
  return user?.role === 'admin'
})

const goBack = () => window.history.back()
const goHome = () => router.get('/')
</script>

<template>
  <!-- Admin sees the real page with a warning banner -->
  <div v-if="isAdmin && isInMaintenance" class="fixed top-0 left-0 right-0 z-[9999] bg-brand-danger px-4 py-2 text-center text-sm font-semibold text-white">
    <Construction class="mr-1 inline h-4 w-4" />
    This page is in maintenance mode — only you can see it. Visitors see a "back later" message.
  </div>

  <!-- Show maintenance page to visitors -->
  <template v-if="isInMaintenance && !isAdmin">
    <Head
      :title="`${pageTitle} — Back Shortly`"
      description="This page is temporarily unavailable while we update the data."
    />
    <Navbar />

    <div class="flex min-h-screen items-center justify-center bg-black px-4 sm:px-6 lg:px-8">
      <div class="w-full max-w-2xl text-center">
        <!-- Icon -->
        <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full border-4 border-brand-accent bg-white/10 backdrop-blur-sm">
          <Construction class="h-10 w-10 text-brand-accent" />
        </div>

        <!-- Title -->
        <MyTextConstructor
          variant="heading"
          alignment="center"
          spacing="tight"
          textColor="text-white"
        >
          <template #myTitle>
            We'll Be Right Back
          </template>
        </MyTextConstructor>

        <!-- Message -->
        <p class="mx-auto mt-4 max-w-lg text-lg leading-relaxed text-white/80 sm:text-xl">
          {{ maintenanceMessage }}
        </p>

        <!-- Buttons -->
        <div class="mt-10 flex flex-col justify-center gap-4 sm:flex-row">
          <MyButtonConstructor
            size="medium"
            variant="outline"
            :icon="ArrowLeft"
            @clicked="goBack"
          >
            Go Back
          </MyButtonConstructor>

          <MyButtonConstructor
            size="medium"
            variant="primary"
            :icon="Home"
            @clicked="goHome"
          >
            Back to Home
          </MyButtonConstructor>
        </div>

        <!-- Reassurance -->
        <p class="mt-8 text-base text-white/50">
          The rest of the site is working as normal.
        </p>
      </div>
    </div>

    <MyFooter variant="gradient" />
  </template>

  <!-- Normal page content (or admin sees it with the banner above) -->
  <template v-else>
    <slot />
  </template>
</template>
