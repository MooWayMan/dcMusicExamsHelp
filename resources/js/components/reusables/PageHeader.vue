<!-- resources/js/components/reusables/PageHeader.vue -->
<script setup lang="ts">
import { computed, useSlots, onMounted, ref, watch, nextTick } from 'vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'

interface Props {
  title: string
  subtitle?: string
  eyebrow?: string
  icon?: string
  showIcon?: boolean
  centerAlign?: boolean
  surface?: 'default' | 'solid' | 'minimal'
  size?: 'default' | 'compact' | 'hero'
  contained?: boolean
  showUnderline?: boolean
  brandLogo?: string
  brandName?: string
  showBrandWordmark?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  subtitle: '',
  eyebrow: '',
  icon: '',
  showIcon: false,
  centerAlign: false,
  surface: 'default',
  size: 'default',
  contained: true,
  showUnderline: false,
  brandLogo: '',
  brandName: '',
  showBrandWordmark: true,
})

const slots = useSlots()

const alignment = computed(() => (props.centerAlign ? 'center' : 'left'))

// Animation state — replays whenever the title changes (i.e. Inertia page navigation)
const animReady = ref(false)

function triggerAnimation() {
  animReady.value = false
  nextTick(() => {
    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        animReady.value = true
      })
    })
  })
}

onMounted(() => {
  triggerAnimation()
})

// PageHeader may persist across navigations if it's in a layout,
// so also watch title changes to replay
watch(() => props.title, () => {
  triggerAnimation()
})

const titleVariant = computed(() => {
  if (props.size === 'compact') return 'heading'
  return 'display'
})

const hasActions = computed(() => Boolean(slots.actions))

// Music note SVG pattern (treble clef, notes, and musical symbols)
const brandIconUrl = 'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/noTagICONmusicexamshelp_logo2.png'

const musicPattern = `url("data:image/svg+xml,%3Csvg width='80' height='80' viewBox='0 0 80 80' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23ffffff' fill-opacity='1'%3E%3Ctext x='10' y='25' font-size='16' font-family='serif'%3E%E2%99%AA%3C/text%3E%3Ctext x='50' y='20' font-size='12' font-family='serif'%3E%E2%99%AC%3C/text%3E%3Ctext x='30' y='55' font-size='14' font-family='serif'%3E%E2%99%A9%3C/text%3E%3Ctext x='65' y='50' font-size='10' font-family='serif'%3E%E2%99%AB%3C/text%3E%3Ctext x='5' y='70' font-size='11' font-family='serif'%3E%E2%99%AB%3C/text%3E%3Ctext x='45' y='75' font-size='16' font-family='serif'%3E%E2%99%AA%3C/text%3E%3C/g%3E%3C/svg%3E")`
</script>

<template>
  <!-- Full-width gradient banner with pattern overlay -->
  <div class="relative w-full overflow-hidden rounded-b-2xl bg-gradient-to-r from-brand-primary via-brand-accent to-brand-primary">
    <!-- Music note pattern overlay -->
    <div class="absolute inset-0 opacity-[0.07]" :style="{ backgroundImage: musicPattern }" />

    <!-- Decorative circles -->
    <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-white/5" />
    <div class="absolute -bottom-8 -left-8 h-32 w-32 rounded-full bg-white/5" />
    <div class="absolute right-1/4 top-1/2 h-20 w-20 -translate-y-1/2 rounded-full bg-white/5" />

    <!-- Content with proper padding -->
    <div class="relative z-10 w-full px-4 py-5 sm:px-6 sm:py-6 lg:px-10 lg:py-10">

      <!--
        RESPONSIVE LAYOUT
        Mobile: compact — logo + eyebrow inline, title, subtitle, action all tight
        Large screens: two-column grid — logo card left, text block right
      -->

      <!-- ===== LARGE SCREEN: side-by-side grid ===== -->
      <div v-if="showBrandWordmark" class="hidden lg:grid lg:grid-cols-[auto_1fr] lg:items-center lg:gap-8">
        <!-- Logo card -->
        <div
          :class="[
            animReady ? 'translate-x-0 scale-100 opacity-100' : '-translate-x-6 scale-90 opacity-0',
            'transition-all duration-500 ease-out'
          ]"
        >
          <div class="inline-block rounded-2xl bg-white/95 p-3 shadow-sm backdrop-blur-sm">
            <img :src="brandIconUrl" alt="musicExams.help" class="h-20 w-auto xl:h-24" />
          </div>
        </div>

        <!-- Text block -->
        <div>
          <div v-if="eyebrow" :class="[animReady ? 'translate-x-0 opacity-100' : '-translate-x-8 opacity-0', 'transition-all duration-500 ease-out']">
            <span class="inline-block rounded-full bg-white/15 px-3 py-1 text-sm font-semibold uppercase tracking-[0.08em] text-white/90 backdrop-blur-sm">{{ eyebrow }}</span>
          </div>
          <div :class="[animReady ? 'translate-y-0 opacity-100' : 'translate-y-6 opacity-0', 'transition-all duration-700 ease-out', eyebrow ? 'mt-3' : '']">
            <MyTextConstructor :variant="titleVariant" alignment="left" text-color="text-white" :show-underline="false" spacing="none" font-family="display">
              <template #myTitle>{{ title }}</template>
            </MyTextConstructor>
          </div>
          <div v-if="subtitle" :class="[animReady ? 'translate-y-0 opacity-100' : 'translate-y-6 opacity-0', 'transition-all duration-700 ease-out delay-200']">
            <MyTextConstructor alignment="left" text-color="text-white/80" sub-title-variant="body" spacing="none" class="mt-1">
              <template #mySubTitle>{{ subtitle }}</template>
            </MyTextConstructor>
          </div>
          <div v-if="hasActions" :class="[animReady ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0', 'mt-5 transition-all duration-500 ease-out delay-300']">
            <slot name="actions" />
          </div>
        </div>
      </div>

      <!-- ===== MOBILE / TABLET: compact stacked ===== -->
      <div :class="[showBrandWordmark ? 'lg:hidden' : '']">
        <!-- Row 1: Logo + Eyebrow + Action button inline -->
        <div
          v-if="showBrandWordmark || eyebrow || hasActions"
          :class="[
            animReady ? 'translate-y-0 opacity-100' : '-translate-y-4 opacity-0',
            'flex flex-wrap items-center justify-center gap-2 sm:gap-3 transition-all duration-500 ease-out'
          ]"
        >
          <!-- Small logo card -->
          <div v-if="showBrandWordmark" class="inline-block rounded-xl bg-white/95 p-1.5 shadow-sm backdrop-blur-sm sm:p-2">
            <img :src="brandIconUrl" alt="musicExams.help" class="h-8 w-auto sm:h-10" />
          </div>
          <!-- Eyebrow pill -->
          <span v-if="eyebrow" class="inline-block rounded-full bg-white/15 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-[0.08em] text-white/90 backdrop-blur-sm sm:px-3 sm:py-1 sm:text-sm">
            {{ eyebrow }}
          </span>
          <!-- Action button inline on mobile -->
          <div v-if="hasActions" class="inline-flex">
            <slot name="actions" />
          </div>
        </div>

        <!-- Row 2: Title -->
        <div
          :class="[
            animReady ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0',
            'transition-all duration-700 ease-out text-center',
            (showBrandWordmark || eyebrow || hasActions) ? 'mt-2 sm:mt-3' : ''
          ]"
        >
          <MyTextConstructor :variant="titleVariant" alignment="center" text-color="text-white" :show-underline="false" spacing="none" font-family="display">
            <template #myTitle>{{ title }}</template>
          </MyTextConstructor>
        </div>

        <!-- Row 3: Subtitle -->
        <div
          v-if="subtitle"
          :class="[
            animReady ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0',
            'mt-1 text-center transition-all duration-700 ease-out delay-200'
          ]"
        >
          <MyTextConstructor alignment="center" text-color="text-white/80" sub-title-variant="body" spacing="none">
            <template #mySubTitle>{{ subtitle }}</template>
          </MyTextConstructor>
        </div>
      </div>

      <!-- ===== NO BRAND WORDMARK: large screen fallback (text only) ===== -->
      <div v-if="!showBrandWordmark" class="hidden lg:block">
        <div v-if="showIcon && icon" class="mb-3 inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-white/10 text-white ring-1 ring-white/20" :class="[animReady ? 'translate-y-0 opacity-100' : '-translate-y-4 opacity-0', 'transition-all duration-500 ease-out']">
          <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path :d="icon" /></svg>
        </div>
        <div v-if="eyebrow" :class="[animReady ? 'translate-x-0 opacity-100' : '-translate-x-8 opacity-0', 'transition-all duration-500 ease-out']">
          <span class="inline-block rounded-full bg-white/15 px-3 py-1 text-sm font-semibold uppercase tracking-[0.08em] text-white/90 backdrop-blur-sm">{{ eyebrow }}</span>
        </div>
        <div :class="[animReady ? 'translate-y-0 opacity-100' : 'translate-y-6 opacity-0', 'transition-all duration-700 ease-out', eyebrow ? 'mt-3' : '']">
          <MyTextConstructor :variant="titleVariant" :alignment="alignment" text-color="text-white" :show-underline="false" spacing="none" font-family="display">
            <template #myTitle>{{ title }}</template>
          </MyTextConstructor>
        </div>
        <div v-if="subtitle" :class="[animReady ? 'translate-y-0 opacity-100' : 'translate-y-6 opacity-0', 'transition-all duration-700 ease-out delay-200']">
          <MyTextConstructor :alignment="alignment" text-color="text-white/80" sub-title-variant="body" spacing="none" class="mt-2">
            <template #mySubTitle>{{ subtitle }}</template>
          </MyTextConstructor>
        </div>
        <div v-if="hasActions" :class="[animReady ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0', 'mt-6 transition-all duration-500 ease-out delay-300']">
          <slot name="actions" />
        </div>
      </div>
    </div>

    <!-- Bottom shine line -->
    <div class="absolute bottom-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-white/30 to-transparent" />
  </div>
</template>
