<!-- resources/js/components/layouts/Head.vue -->
<script setup lang="ts">
import { computed } from 'vue'
import { Head as InertiaHead } from '@inertiajs/vue3'

const APP_NAME = import.meta.env.VITE_APP_NAME || 'My App'

interface Props {
  title?: string
  description?: string
  keywords?: string
  author?: string
  robots?: string
  viewport?: string
  charset?: string
  ogTitle?: string
  ogDescription?: string
  ogImage?: string
  ogUrl?: string
  twitterCard?: string
  twitterSite?: string
  canonicalUrl?: string
}

const props = withDefaults(defineProps<Props>(), {
  title: '',
  description: '',
  keywords: '',
  author: '',
  robots: 'index,follow',
  viewport: 'width=device-width, initial-scale=1',
  charset: 'utf-8',
  ogTitle: '',
  ogDescription: '',
  ogImage: '',
  ogUrl: '',
  twitterCard: 'summary_large_image',
  twitterSite: '',
  canonicalUrl: '',
})

const computedTitle = computed(() => {
  return props.title ? `${props.title} - ${APP_NAME}` : APP_NAME
})

const computedDescription = computed(() => {
  return props.description || 'Book Trinity College London music exams through centre 120. Digital and face-to-face grades, free certificates, recognition and incentives for students and teachers.'
})

const computedOgTitle = computed(() => {
  return props.ogTitle || computedTitle.value
})

const computedOgDescription = computed(() => {
  return props.ogDescription || computedDescription.value
})

const DEFAULT_OG_IMAGE = 'https://dcmusicexamshelp.s3.eu-west-2.amazonaws.com/logos/musicexamshelp_logo2.png'

const computedOgImage = computed(() => {
  return props.ogImage || DEFAULT_OG_IMAGE
})
</script>

<template>
  <InertiaHead>
    <title>{{ computedTitle }}</title>

    <meta :charset="props.charset" />
    <meta name="viewport" :content="props.viewport" />
    <meta name="description" :content="computedDescription" />
    <meta name="robots" :content="props.robots" />

    <meta v-if="props.keywords" name="keywords" :content="props.keywords" />
    <meta v-if="props.author" name="author" :content="props.author" />

    <!-- Open Graph -->
    <meta property="og:type" content="website" />
    <meta property="og:site_name" content="musicExams.help" />
    <meta property="og:title" :content="computedOgTitle" />
    <meta property="og:description" :content="computedOgDescription" />
    <meta property="og:image" :content="computedOgImage" />
    <meta v-if="props.ogUrl" property="og:url" :content="props.ogUrl" />

    <!-- Twitter -->
    <meta name="twitter:card" :content="props.twitterCard" />
    <meta name="twitter:title" :content="computedOgTitle" />
    <meta name="twitter:description" :content="computedOgDescription" />
    <meta name="twitter:image" :content="computedOgImage" />
    <meta v-if="props.twitterSite" name="twitter:site" :content="props.twitterSite" />

    <!-- Canonical -->
    <link v-if="props.canonicalUrl" rel="canonical" :href="props.canonicalUrl" />
  </InertiaHead>
</template>