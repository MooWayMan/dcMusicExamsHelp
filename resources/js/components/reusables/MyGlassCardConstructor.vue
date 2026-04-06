<!-- resources/js/components/reusables/MyGlassCardConstructor.vue -->
<!--
  Glass card with branded gradient header, glass body and optional gradient footer.
  Designed for use on dark/image background sections.

  Usage:
  <MyGlassCardConstructor
    :cards="[{ icon: Award, title: 'Recognition', detail: 'Teachers who...', link: '/page', linkText: 'Find out more' }]"
    :columns="2"
  />
-->
<script setup lang="ts">
import { type Component } from 'vue'
import { ArrowRight } from 'lucide-vue-next'

interface GlassCard {
  icon?: Component
  title: string
  subtitle?: string
  detail: string
  link?: string
  linkText?: string
  [key: string]: unknown
}

interface Props {
  cards: GlassCard[]
  columns?: 1 | 2 | 3 | 4
  showFooter?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  columns: 2,
  showFooter: true,
})

const gridClass = {
  1: 'grid-cols-1',
  2: 'grid-cols-1 sm:grid-cols-2',
  3: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
  4: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
}
</script>

<template>
  <div :class="['grid gap-6', gridClass[columns]]">
    <div
      v-for="card in cards"
      :key="card.title"
      class="flex h-full flex-col overflow-hidden rounded-2xl border-4 border-brand-accent bg-white/10 shadow-2xl backdrop-blur-sm transition-all duration-200 hover:-translate-y-1 hover:bg-white/15 hover:shadow-[0_25px_60px_-12px_rgba(0,0,0,0.5)]"
    >
      <!-- Header — black bar with icon + title -->
      <div class="flex w-full items-center gap-3 bg-black px-5 py-3 sm:px-6">
        <component v-if="card.icon" :is="card.icon" class="h-5 w-5 shrink-0 text-brand-accent sm:h-6 sm:w-6" />
        <div>
          <p class="text-base font-semibold text-white sm:text-lg">{{ card.title }}</p>
          <p v-if="card.subtitle" class="text-xs font-medium uppercase tracking-wide text-white/60">{{ card.subtitle }}</p>
        </div>
      </div>

      <!-- Body — glass area with description -->
      <div class="flex-1 p-5 sm:p-6">
        <p class="text-base leading-snug text-white sm:text-base md:text-lg" v-html="card.detail"></p>
        <slot :name="`body-${card.title}`" />
      </div>

      <!-- Footer — gradient bar with CTA link -->
      <a
        v-if="showFooter && card.link"
        :href="card.link"
        class="flex items-center justify-center gap-2 bg-black px-5 py-3 text-sm font-semibold text-white transition hover:opacity-90"
      >
        {{ card.linkText || 'Find out more' }}
        <ArrowRight class="h-4 w-4" />
      </a>
    </div>
  </div>
</template>
