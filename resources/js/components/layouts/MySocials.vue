<!-- resources/js/components/layouts/MySocials.vue -->
<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  size?: 'small' | 'medium' | 'large'
  layout?: 'horizontal' | 'vertical'
  spacing?: 'tight' | 'normal' | 'loose'
  hoverEffect?: boolean
  showLabels?: boolean
  iconColor?: string
  hoverColor?: string
}

const props = withDefaults(defineProps<Props>(), {
  size: 'medium',
  layout: 'horizontal',
  spacing: 'normal',
  hoverEffect: true,
  showLabels: false,
  iconColor: 'text-brand-primary',
  hoverColor: 'hover:text-brand-accent',
})

interface SocialLink {
  name: string
  url: string
  icon: string
  srLabel: string
}

const socialLinks: SocialLink[] = [
  {
    name: 'X (Twitter)',
    url: 'https://x.com/MooWayMan',
    srLabel: 'X',
    icon: `<path d="M14.095479,10.316482L22.286354,1h-1.940718l-7.115352,8.087682L7.551414,1H1l8.589488,12.231093L1,23h1.940717l7.509372-8.542861L16.448587,23H23L14.095479,10.316482z M11.436522,13.338465l-0.871624-1.218704l-6.924311-9.68815h2.981339l5.58978,7.82155l0.867949,1.218704l7.26506,10.166271h-2.981339L11.436522,13.338465z"/>`,
  },
]

const iconSizes: Record<NonNullable<Props['size']>, string> = {
  small: 'h-5 w-5',
  medium: 'h-6 w-6',
  large: 'h-8 w-8',
}

const containerClasses = computed(() => {
  const layoutMap: Record<
    NonNullable<Props['layout']>,
    Record<NonNullable<Props['spacing']>, string>
  > = {
    horizontal: {
      tight: 'flex flex-row items-center gap-2',
      normal: 'flex flex-row items-center gap-4',
      loose: 'flex flex-row items-center gap-6',
    },
    vertical: {
      tight: 'flex flex-col items-start gap-2',
      normal: 'flex flex-col items-start gap-3',
      loose: 'flex flex-col items-start gap-5',
    },
  }

  return layoutMap[props.layout][props.spacing]
})

const linkClasses = computed(() => [
  'group relative inline-flex items-center transition-all duration-200',
  props.iconColor,
  props.hoverEffect ? props.hoverColor : '',
  props.showLabels ? 'gap-2' : '',
])

const iconSize = computed(() => iconSizes[props.size])
</script>

<template>
  <div :class="containerClasses">
    <a
      v-for="social in socialLinks"
      :key="social.name"
      :href="social.url"
      target="_blank"
      rel="noopener noreferrer"
      :class="linkClasses"
      :aria-label="`Visit our ${social.name} page`"
    >
      <span class="sr-only">{{ social.srLabel }}</span>

      <svg
        :class="[
          iconSize,
          'transition-transform duration-200',
          hoverEffect ? 'group-hover:-translate-y-0.5' : '',
        ]"
        fill="currentColor"
        viewBox="0 0 24 24"
        aria-hidden="true"
      >
        <g v-html="social.icon"></g>
      </svg>

      <span
        v-if="!showLabels"
        class="pointer-events-none absolute bottom-full left-1/2 mb-2 -translate-x-1/2 rounded-md bg-brand-text px-2 py-1 text-xs whitespace-nowrap text-white opacity-0 transition-opacity duration-200 group-hover:opacity-100"
      >
        {{ social.name }}
      </span>

      <span
        v-if="showLabels"
        class="text-sm font-medium"
      >
        {{ social.name }}
      </span>
    </a>
  </div>
</template>

<style scoped>
a:focus-visible {
  outline: 2px solid currentColor;
  outline-offset: 4px;
  border-radius: 0.5rem;
}
</style>