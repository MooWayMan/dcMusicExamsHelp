<!-- resources/js/pages/ComingSoon.vue -->
<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import AppLayout from '@/components/layouts/AppLayout.vue'
import Head from '@/components/layouts/Head.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'

interface Props {
  pageTitle?: string
  description?: string
  expectedDate?: string
}

const props = withDefaults(defineProps<Props>(), {
  pageTitle: 'Coming Soon',
  description: 'This page is currently under development and will be available soon.',
  expectedDate: 'Stay tuned for updates!',
})

const goBack = () => window.history.back()
const goHome = () => router.get('/')
</script>

<template>
  <AppLayout>
    <Head
      :title="props.pageTitle"
      :description="props.description"
    />

    <div class="flex min-h-screen items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100 px-4 sm:px-6 lg:px-8">
      <div class="w-full max-w-3xl rounded-3xl border border-brand-border bg-white p-8 text-center shadow-2xl sm:p-12">
        <MyTextConstructor
          variant="display"
          alignment="center"
          fontFamily="display"
          spacing="tight"
          textColor="text-brand-primary"
        >
          <template #myTitle>
            {{ props.pageTitle }}
          </template>
        </MyTextConstructor>

        <MyTextConstructor
          bodyVariant="body"
          alignment="center"
          spacing="none"
          textColor="text-brand-text-soft"
          class="mt-4"
        >
          {{ props.description }}
        </MyTextConstructor>

        <div class="mt-8 rounded-2xl bg-brand-bg p-5">
          <MyTextConstructor
            variant="subheading"
            alignment="center"
            spacing="none"
            textColor="text-brand-primary"
          >
            <template #myTitle>
              {{ props.expectedDate }}
            </template>
          </MyTextConstructor>
        </div>

        <div class="flex flex-col justify-center gap-4 pt-8 sm:flex-row">
          <MyButtonConstructor
            size="large"
            variant="outline"
            class="sm:w-auto"
            @clicked="goBack"
          >
            Go Back
          </MyButtonConstructor>

          <MyButtonConstructor
            size="large"
            variant="primary"
            class="sm:w-auto"
            @clicked="goHome"
          >
            Back to Home
          </MyButtonConstructor>
        </div>

        <div class="mt-8 border-t border-brand-border pt-8">
          <MyTextConstructor
            variant="muted"
            alignment="center"
            spacing="none"
            textColor="text-brand-text-soft"
          >
            <template #mySubTitle>
              We’re working hard to bring you new features and content. Thank you for your patience!
            </template>
          </MyTextConstructor>
        </div>
      </div>
    </div>
  </AppLayout>
</template>